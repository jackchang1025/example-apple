<?php

namespace Modules\AppleClient\Service\Resources\Web\Idmsa;

use Modules\AppleClient\Events\VerifySecurityCodeFailEvent;
use Modules\AppleClient\Events\VerifySecurityCodeSuccessEvent;
use Modules\AppleClient\Service\Cookies\CookieAuthenticator;
use Modules\AppleClient\Service\Cookies\CookieJarFactory;
use Modules\AppleClient\Service\DataConstruct\NullData;
use Modules\AppleClient\Service\DataConstruct\PhoneNumber;
use Modules\AppleClient\Service\Exception\AccountException;
use Modules\AppleClient\Service\Exception\MaxRetryAttemptsException;
use Modules\AppleClient\Service\Exception\PhoneAddressException;
use Modules\AppleClient\Service\Exception\PhoneNotFoundException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Exception\VerificationCodeSentTooManyTimesException;
use Modules\AppleClient\Service\Header\HeaderSynchronizeFactory;
use Modules\AppleClient\Service\Header\HeaderSynchronizeInterface;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\Auth\Auth;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SendVerificationCode\SendDeviceSecurityCode;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SendVerificationCode\SendPhoneVerificationCode;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\SignIn\SignInComplete;
use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\VerifyPhoneSecurityCode\VerifyPhoneSecurityCode;
use Modules\AppleClient\Service\Resources\Web\WebResource;
use Modules\AppleClient\Service\Trait\HasTries;
use Modules\PhoneCode\Service\Exception\AttemptBindPhoneCodeException;
use Modules\PhoneCode\Service\Helpers\PhoneCodeParser;
use Modules\PhoneCode\Service\PhoneCodeService;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Spatie\LaravelData\DataCollection;
use Modules\AppleClient\Service\Integrations\Idmsa\IdmsaConnector;
use Throwable;
use function VeeWee\Xml\Dom\Xpath\Configurator\functions;

/**
 *
 * 负责处理与 IDMSA 相关的苹果认证功能，包括登录、两步验证等。
 */
class IdmsaResource
{
    use HasTries;

    /**
     * @var Auth|null 当前认证对象
     */
    protected static ?Auth $auth = null;

    protected ?IdmsaConnector $idmsaConnector = null;

    protected ?CookieAuthenticator $authenticator = null;
    protected ?HeaderSynchronizeInterface $headerSynchronize = null;


    /**
     * WebAuthenticationResources 构造函数
     *
     * @param WebResource $webResource
     * @param PhoneCodeService $phoneCodeService 手机验证码服务
     * @param CookieJarFactory $cookieJarFactory
     * @param HeaderSynchronizeFactory $headerSynchronizeFactory
     * @param int|null $retryInterval
     * @param bool|null $useExponentialBackoff
     * @param int|null $tries
     */
    public function __construct(
        protected WebResource $webResource,
        protected PhoneCodeService $phoneCodeService,
        protected CookieJarFactory $cookieJarFactory,
        protected HeaderSynchronizeFactory $headerSynchronizeFactory,
        ?int $retryInterval = 1000,
        ?bool $useExponentialBackoff = true,
        ?int $tries = 5
    ) {
        $this->withTries($tries)
            ->withRetryInterval($retryInterval)
            ->withUseExponentialBackoff($useExponentialBackoff);
    }

    /**
     * 设置 IdmsaConnector 实例（主要用于测试）
     *
     * @param IdmsaConnector $connector
     * @return $this
     */
    public function setIdmsaConnector(IdmsaConnector $connector): self
    {
        $this->idmsaConnector = $connector;

        return $this;
    }

    public function getAuthenticator(): CookieAuthenticator
    {
        return $this->authenticator ??= new CookieAuthenticator(
            $this->cookieJarFactory->create('idmsa', $this->getWebResource()->getApple()->getAccount()->getSessionId())
        );
    }

    public function getHeaderSynchronize(): HeaderSynchronizeInterface
    {
        return $this->headerSynchronize ??= $this->headerSynchronizeFactory->create(
            'idmsa',
            $this->getWebResource()->getApple()->getAccount()->getSessionId()
        );
    }

    public function getIdmsaConnector(): IdmsaConnector
    {
        return $this->idmsaConnector ??= new IdmsaConnector(
            apple: $this->getWebResource()->getApple(),
            authenticator: $this->getAuthenticator(),
            headerSynchronize: $this->getHeaderSynchronize(),
            serviceKey: $this->getWebResource()->getApple()->getConfig()->getServiceKey(),
            redirectUri: $this->getWebResource()->getApple()->getConfig()->getApiUrl()
        );
    }

    public function getWebResource(): WebResource
    {
        return $this->webResource;
    }

    /**
     * 用户登录方法
     *
     * @return SignInComplete 登录完成的响应数据
     * @throws FatalRequestException 请求失败异常
     * @throws RequestException 请求异常
     * @throws \JsonException JSON 解析异常
     */
    public function signIn(): SignInComplete
    {
        $account = $this->getWebResource()->getApple()->getAccount();

        try {

            $initData = $this->getWebResource()
                ->getAppleAuthenticationConnector()
                ->getAuthenticationResource()
                ->signInInit(
                    $account->getAccount()
                );

            $signInInitData = $this
                ->getIdmsaConnector()
                ->getAuthenticateResources()
                ->signInInit(a: $initData->value, account: $account->getAccount());

            $completeResponse = $this->getWebResource()
                ->getAppleAuthenticationConnector()
                ->getAuthenticationResource()
                ->signInComplete(
                    \Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Dto\Request\SignInComplete::from(
                        [
                            'key'       => $initData->key,
                            'salt'      => $signInInitData->salt,
                            'b'         => $signInInitData->b,
                            'c'         => $signInInitData->c,
                            'password'  => $account->getPassword(),
                            'iteration' => $signInInitData->iteration,
                            'protocol'  => $signInInitData->protocol,
                        ]
                    )
                );

            $reponse = $this
                ->getIdmsaConnector()
                ->getAuthenticateResources()
                ->signInComplete(
                    \Modules\AppleClient\Service\Integrations\Idmsa\Dto\Request\SignIn\SignInComplete::from([
                        'account' => $account->getAccount(),
                        'm1'      => $completeResponse->M1,
                        'm2'      => $completeResponse->M2,
                        'c'       => $completeResponse->c,
                    ])
                );

            // 发送登录成功事件
            $this->getWebResource()->getApple()->getDispatcher()?->dispatch(
                new \Modules\AppleClient\Events\AccountLoginSuccessEvent(account: $account)
            );

            return $reponse;

        } catch (FatalRequestException|RequestException $e) {
            // 发送登录失败事件
            $this->getWebResource()->getApple()->getDispatcher()?->dispatch(
                new \Modules\AppleClient\Events\AccountLoginFailEvent(account: $account)
            );

            throw $e;
        }
    }

    /**
     * 两步验证方法
     *
     * @return VerifyPhoneSecurityCode 验证手机安全码的响应数据
     * @throws PhoneAddressException 未绑定手机号或手机号地址异常
     * @throws PhoneNotFoundException 未找到可信手机号
     * @throws MaxRetryAttemptsException|\Throwable 超过最大重试次数
     */
    public function twoFactorAuthentication(): VerifyPhoneSecurityCode
    {
        $account = $this->getWebResource()->getApple()->getAccount();

        if ($account->bindPhone === null) {
            throw new PhoneAddressException("未绑定手机号");
        }

        if ($account->bindPhoneAddress === null) {
            throw new PhoneAddressException("未绑定手机号地址");
        }

        $trustedPhones = $this->filterTrustedPhone();
        if ($trustedPhones->count() === 0) {
            throw new PhoneNotFoundException("未找到可信手机号");
        }

        return $this->attemptVerifyPhoneCode($trustedPhones);
    }

    /**
     * 过滤可信的手机号码
     *
     * @return DataCollection 可信手机号码集合
     * @throws \Throwable
     */
    public function filterTrustedPhone(): DataCollection
    {
        return $this->getAuth()->filterTrustedPhone($this->getWebResource()->getApple()->getAccount()->bindPhone);
    }

    /**
     * 获取认证对象
     *
     * @return Auth 当前认证对象
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function getAuth(): Auth
    {
        return self::$auth ??= $this->getIdmsaConnector()->getAuthenticateResources()->auth();
    }

    /**
     * 尝试验证手机验证码
     *
     * @param DataCollection $phoneList 手机号码列表
     * @return VerifyPhoneSecurityCode 验证响应数据
     * @throws MaxRetryAttemptsException|\Throwable 所有手机号验证均失败
     */
    protected function attemptVerifyPhoneCode(DataCollection $phoneList): VerifyPhoneSecurityCode
    {
        return $this->handleRetry(callback: function () use ($phoneList) {

            foreach ($phoneList as $phone) {
                try {

                    $code = $this->getPhoneVerificationCode($phone);

                    return $this->verifyPhoneVerificationCode($phone, $code);

                } catch (VerificationCodeException|AttemptBindPhoneCodeException $e) {

                    continue;
                }
            }

            throw new MaxRetryAttemptsException("所有手机号验证均失败");
        }, retryWhenCallback: function (Throwable $e) {

            return $e instanceof VerificationCodeException || $e instanceof AttemptBindPhoneCodeException;
        });
    }

    /**
     * @param DataCollection $phoneList
     * @return string
     * @throws AccountException
     * @throws FatalRequestException
     * @throws MaxRetryAttemptsException
     * @throws PhoneNotFoundException
     * @throws RequestException
     */
    public function getTrustedPhoneCode(DataCollection $phoneList): string
    {
        foreach ($phoneList as $phone) {
            try {

                return $this->getPhoneVerificationCode($phone);

            } catch (VerificationCodeException|AttemptBindPhoneCodeException $e) {

                continue;
            }
        }

        throw new MaxRetryAttemptsException("所有手机号验证均失败");
    }

    /**
     * 获取手机验证码
     *
     * @param PhoneNumber $phone 手机号码对象
     * @return string 手机验证码
     * @throws AccountException 未绑定手机号地址或手机号地址无效
     * @throws FatalRequestException 请求失败异常
     * @throws RequestException|AttemptBindPhoneCodeException|PhoneNotFoundException 请求异常
     */
    public function getPhoneVerificationCode(PhoneNumber $phone): string
    {
        $account = $this->getWebResource()->getApple()->getAccount();

        if (!$account->bindPhoneAddress) {
            throw new AccountException("未绑定手机号地址");
        }

        if (!$this->validatePhoneAddress()) {
            throw new AccountException("手机号地址无效");
        }

        // 发送手机安全码
        $this->sendPhoneSecurityCode($phone);

        // 等待安全码发送
        $this->waitForCodeDelivery();

        // 获取手机验证码
        return $this->getPhoneCodeService()
            ->attemptGetPhoneCode($account->bindPhoneAddress, new PhoneCodeParser());
    }

    /**
     * 验证手机号地址的有效性
     *
     * @return bool 是否有效
     */
    protected function validatePhoneAddress(): bool
    {
        try {
            return \Illuminate\Support\Facades\Http::get(
                $this->getWebResource()->getApple()->getAccount()->bindPhoneAddress
            )->successful();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * 发送手机安全码
     *
     * @param PhoneNumber $phone 手机号码对象
     * @return SendPhoneVerificationCode 发送响应数据
     * @throws FatalRequestException 请求失败异常
     * @throws RequestException 请求异常
     * @throws VerificationCodeSentTooManyTimesException 发送次数过多异常
     */
    public function sendPhoneSecurityCode(PhoneNumber $phone): SendPhoneVerificationCode
    {

        try {

            $response = $this->getIdmsaConnector()->getAuthenticateResources()->sendPhoneSecurityCode($phone->id);

            // 发送发送成功事件
            $this->getWebResource()->getApple()->getDispatcher()?->dispatch(
                new \Modules\AppleClient\Events\SendPhoneSecurityCodeSuccessEvent(
                    $this->getWebResource()->getApple()->getAccount(), $phone
                )
            );

            return $response;

        } catch (\Exception $e) {

            // 发送发送失败事件
            $this->getWebResource()->getApple()->getDispatcher()?->dispatch(
                new \Modules\AppleClient\Events\SendPhoneSecurityCodeFailEvent(
                    $this->getWebResource()->getApple()->getAccount(), $phone
                )
            );

            throw $e;
        }
    }

    /**
     * 发送验证码
     *
     * @return SendDeviceSecurityCode 发送响应数据
     * @throws FatalRequestException 请求失败异常
     * @throws RequestException 请求异常
     */
    public function sendVerificationCode(): SendDeviceSecurityCode
    {
        try {

            $response = $this->getIdmsaConnector()->getAuthenticateResources()->sendSecurityCode();

            // 发送发送成功事件
            $this->getWebResource()->getApple()->getDispatcher()?->dispatch(
                new \Modules\AppleClient\Events\SendVerificationCodeSuccessEvent(
                    $this->getWebResource()->getApple()->getAccount()
                )
            );

            return $response;

        } catch (\Exception $e) {
            // 发送发送失败事件
            $this->getWebResource()->getApple()->getDispatcher()?->dispatch(
                new \Modules\AppleClient\Events\SendVerificationCodeFailEvent(
                    $this->getWebResource()->getApple()->getAccount()
                )
            );

            throw $e;
        }
    }

    /**
     * 等待验证码发送完成
     *
     * @return void
     */
    protected function waitForCodeDelivery(): void
    {
        usleep($this->getSleepTime(1, $this->getRetryInterval(), false));
    }

    /**
     * 获取手机验证码服务
     *
     * @return PhoneCodeService 手机验证码服务
     */
    public function getPhoneCodeService(): PhoneCodeService
    {
        return $this->phoneCodeService;
    }

    /**
     * 验证手机验证码
     *
     * @param PhoneNumber $phone 手机号码对象
     * @param string $code 验证码
     * @return VerifyPhoneSecurityCode 验证响���数据
     * @throws FatalRequestException 请求失败异常
     * @throws RequestException 请求异常
     * @throws VerificationCodeException 验证码异常
     */
    public function verifyPhoneVerificationCode(PhoneNumber $phone, string $code): VerifyPhoneSecurityCode
    {
        try {

            $response = $this->getIdmsaConnector()->getAuthenticateResources()->verifyPhoneCode($phone->id, $code);

            // 发送验证成功事件
            $this->getWebResource()->getApple()->getDispatcher()?->dispatch(
                new VerifySecurityCodeSuccessEvent($this->getWebResource()->getApple()->getAccount(), $code, $phone)
            );

            return $response;

        } catch (\Exception $e) {

            // 发送验证失败事件
            $this->getWebResource()->getApple()->getDispatcher()?->dispatch(
                new VerifySecurityCodeFailEvent($this->getWebResource()->getApple()->getAccount(), $code, $phone)
            );

            throw $e;
        }
    }

    /**
     * 验证安全码
     *
     * @param string $code 安全码
     * @return NullData 验证响应数据
     * @throws RequestException|FatalRequestException|\JsonException 请求异常
     */
    public function verifySecurityCode(string $code): NullData
    {
        try {
            $response = $this->getIdmsaConnector()->getAuthenticateResources()->verifySecurityCode($code);

            // 发送验证成功事件
            $this->getWebResource()->getApple()->getDispatcher()?->dispatch(
                new VerifySecurityCodeSuccessEvent($this->getWebResource()->getApple()->getAccount(), $code)
            );

            return $response;

        } catch (\Exception $e) {

            // 发送验证失败事件
            $this->getWebResource()->getApple()->getDispatcher()?->dispatch(
                new VerifySecurityCodeFailEvent($this->getWebResource()->getApple()->getAccount(), $code)
            );

            throw $e;
        }
    }
}
