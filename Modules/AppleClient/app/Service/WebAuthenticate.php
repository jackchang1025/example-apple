<?php

namespace Modules\AppleClient\Service;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\AppleClient\Service\DataConstruct\PhoneNumber;
use Modules\AppleClient\Service\DataConstruct\SendVerificationCode\SendPhoneVerificationCode;
use Modules\AppleClient\Service\DataConstruct\VerifyPhoneSecurityCode\VerifyPhoneSecurityCode;
use Modules\AppleClient\Service\Exception\AccountException;
use Modules\AppleClient\Service\Exception\MaxRetryAttemptsException;
use Modules\AppleClient\Service\Exception\PhoneAddressException;
use Modules\AppleClient\Service\Exception\PhoneNotFoundException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Auth\AuthData;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\SignInCompleteData;
use Modules\AppleClient\Service\Trait\HasTries;
use Modules\PhoneCode\Service\Exception\AttemptBindPhoneCodeException;
use Modules\PhoneCode\Service\Helpers\PhoneCodeParser;
use Spatie\LaravelData\DataCollection;

class WebAuthenticate
{
    use HasTries;

    protected static ?AuthData $auth = null;

    public function __construct(
        protected readonly AppleAccountManager $accountManager,
    ) {
        $this->initializeRetrySettings();
    }

    // 初始化重试设置
    protected function initializeRetrySettings(): void
    {
        $this->withTries(3)
            ->withRetryInterval(1000)
            ->withUseExponentialBackoff(true);
    }

    /**
     * @return SignInCompleteData
     * @throws \JsonException
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function signIn(): SignInCompleteData
    {
        $account = $this->getAccountManager()->getAccount();

        $initData = $this->getAccountManager()->getClient()->getAppleAuthConnector()->getSignInResources()->signInInit(
            $account->account
        );

        $signInInitData = $this->getAccountManager()->getClient()->getIdmsaConnector()->getAuthenticateResources(
        )->signInInit(a: $initData->value, account: $account->account);

        $completeResponse = $this->getAccountManager()->getClient()->getAppleAuthConnector()->getSignInResources(
        )->signInComplete(
            key: $initData->key,
            salt: $signInInitData->salt,
            b: $signInInitData->b,
            c: $signInInitData->c,
            password: $account->password,
            iteration: $signInInitData->iteration,
            protocol: $signInInitData->protocol
        );

        return $this->getAccountManager()->getClient()->getIdmsaConnector()->getAuthenticateResources()->signInComplete(
            account: $account,
            m1: $completeResponse->M1,
            m2: $completeResponse->M2,
            c: $completeResponse->c,
        );
    }

    // 执行登录流程

    public function getAccountManager(): AppleAccountManager
    {
        return $this->accountManager;
    }

    public function loginAndVerify(): VerifyPhoneSecurityCode
    {
        $this->login();
        $trustedPhones = $this->getTrustedPhones();

        return $this->verifyPhone($trustedPhones);
    }

    // 执行完整的登录和验证流程

    public function getTrustedPhones(): DataCollection
    {
        $phoneList = $this->getAuth()->getTrustedPhoneNumbers();

        return $this->filterTrustedPhone($phoneList);
    }


    public function getAuth(): AuthData
    {
        return self::$auth ??= $this->getAccountManager()->getClient()->getIdmsaConnector()->getAuthenticateResources(
        )->auth();
    }

    // 为单个手机号发送验证码

    /**
     * @param DataCollection $trustedPhones
     * @return DataCollection
     * @throws \JsonException
     * @throws \Throwable
     */
    public function filterTrustedPhone(DataCollection $trustedPhones): DataCollection
    {
        $account = $this->getAccountManager()->getAccount();

        throw_if(empty($account->bind_phone), MaxRetryAttemptsException::class, "未绑定手机号");

        $phoneList = $trustedPhones->filter(
            fn(PhoneNumber $phone) => Str::contains($account->bind_phone, $phone->lastTwoDigits)
        );

        throw_if($phoneList->count() === 0, PhoneNotFoundException::class, "该账号未绑定该手机号码，无法授权登陆: ");

        return $phoneList;
    }

    // 验证单个手机号的验证码

    public function verifyPhone(DataCollection $trustedPhoneList): VerifyPhoneSecurityCode
    {
        return $this->attemptVerifyPhoneCode($trustedPhoneList);
    }

    // 获取手机验证码

    protected function attemptVerifyPhoneCode(DataCollection $phoneList): VerifyPhoneSecurityCode
    {


        return $this->handleRetry(function () use ($phoneList) {
            foreach ($phoneList as $phone) {
                try {
                    return $this->processPhoneVerification($phone);
                } catch (VerificationCodeException|AttemptBindPhoneCodeException $e) {
                    $this->handleVerificationError($phone, $e);
                    continue;
                }
            }
            throw new MaxRetryAttemptsException("所有手机号验证均失败");
        });
    }

    protected function processPhoneVerification(PhoneNumber $phone): VerifyPhoneSecurityCode
    {
        $this->sendVerificationCodeForPhone($phone);
        $this->waitForCodeDelivery();
        $code = $this->getPhoneCode();

        return $this->verifyCodeForPhone($phone, $code);
    }

    public function sendVerificationCodeForPhone(PhoneNumber $phone): SendPhoneVerificationCode
    {
        return $this->getAccountManager()->sendPhoneSecurityCode($phone->id);
    }

    protected function waitForCodeDelivery(): void
    {
        usleep($this->getSleepTime(1, $this->getRetryInterval(), false));
    }

    // 处理单个手机的验证流程

    public function getPhoneCode(): string
    {
        $account = $this->getAccountManager()->getAccount();

        throw_if(!$account->bind_phone_address, AccountException::class, "未绑定手机号地址");

        throw_if(!$this->validatePhoneAddress(), PhoneAddressException::class, "手机号地址无效");


        return $this->getAccountManager()
            ->getPhoneCodeService()
            ->attemptGetPhoneCode(
                $account->bind_phone_address,
                new PhoneCodeParser()
            );
    }

    // 处理验证错误

    protected function validatePhoneAddress(): bool
    {
        try {

            return Http::get($this->getAccountManager()->getAccount()->bind_phone_address)->successful();
        } catch (\Exception) {
            return false;
        }
    }

    // 等待验证码发送

    public function verifyCodeForPhone(PhoneNumber $phone, string $code): VerifyPhoneSecurityCode
    {
        return $this->getAccountManager()->verifyPhoneCode($phone->id, $code);
    }

    protected function handleVerificationError(PhoneNumber $phone, \Exception $e): void
    {
        $this->errorNotification(
            "授权失败",
            "phone id: {$phone->id} 验证失败: {$e->getMessage()}"
        );
    }

    public function errorNotification(string $title, string $message): void
    {
        Log::error($message);
        Notification::make()
            ->title($title)
            ->body($message)
            ->warning()
            ->sendToDatabase(Auth::user());
    }

    public function attemptGetVerificationFromPhones(DataCollection $phones): string
    {
        return $this->handleRetry(function () use ($phones) {
            foreach ($phones as $phone) {
                try {
                    // 发送验证码
                    $this->sendVerificationCodeForPhone($phone);

                    // 等待验证码送达
                    $this->waitForCodeDelivery();

                    // 获取验证码
                    return $this->getPhoneCode();

                } catch (AttemptBindPhoneCodeException $e) {
                    $this->handleVerificationError($phone, $e);
                    continue;
                }
            }

            throw new MaxRetryAttemptsException("无法从任何匹配的手机获取验证码");
        });
    }

    protected function validatePhoneBinding(): void
    {
        $account = $this->getAccountManager()->getAccount();

        throw_if(empty($account->bind_phone), AccountException::class, "未绑定手机号");
        throw_if(empty($account->bind_phone_address), AccountException::class, "未绑定手机号地址");
        throw_if(!$this->validatePhoneAddress(), PhoneAddressException::class, "手机号地址无效");
    }
}
