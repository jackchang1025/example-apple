<?php

declare(strict_types=1);

namespace App\Apple\Service;

use App\Apple\Service\Client\Response;
use App\Events\AccountBindPhoneFailEvent;
use App\Events\AccountBindPhoneSuccessEvent;
use App\Apple\Service\Exception\{
    AttemptBindPhoneCodeException,
    MaxRetryAttemptsException,
    BindPhoneCodeException,
    UnauthorizedException
};
use App\Models\{Account, Phone, User};
use Exception;
use Filament\Notifications\Notification;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Psr\Log\LoggerInterface;
use Throwable;

class AccountBind
{
    protected const int MAX_RETRY_ATTEMPTS = 10;
    protected const int PHONE_CODE_RETRY_ATTEMPTS = 10;
    protected const int PHONE_CODE_RETRY_DELAY = 10;
    protected const int|float PHONE_CODE_WAIT_TIME = 120; // 2 minutes

    protected array $usedPhoneIds = [];

    protected ?Account $account = null;

    protected ?Phone $phone = null;

    public function __construct(
        protected readonly Apple $apple,
        protected readonly LoggerInterface $logger,
        protected readonly int $maxRetryAttempts = self::MAX_RETRY_ATTEMPTS,
        protected readonly int $phoneCodeRetryAttempts = self::PHONE_CODE_RETRY_ATTEMPTS,
        protected readonly int $phoneCodeRetryDelay = self::PHONE_CODE_RETRY_DELAY,
        protected readonly int $phoneCodeWaitTime = self::PHONE_CODE_WAIT_TIME
    ) {
    }

    public function getMaxRetryAttempts(): int
    {
        return $this->maxRetryAttempts;
    }

    public function getPhoneCodeRetryAttempts(): int
    {
        return $this->phoneCodeRetryAttempts;
    }

    public function getPhoneCodeRetryDelay(): int
    {
        return $this->phoneCodeRetryDelay;
    }

    public function getPhoneCodeWaitTime(): int
    {
        return $this->phoneCodeWaitTime;
    }

    public function getUsedPhoneIds(): array
    {
        return $this->usedPhoneIds;
    }

    /**
     * @param int $id
     * @return void
     * @throws BindPhoneCodeException
     * @throws GuzzleException
     * @throws MaxRetryAttemptsException
     * @throws UnauthorizedException
     * @throws \Throwable
     */
    public function handle(int $id): void
    {
        try {

            $this->validateAccount($this->account = $this->getAccount($id));

            $this->authenticateApple();

            $this->attemptBind();

        } catch (\Throwable  $e) {
            $this->handleException($e);
            throw $e;
        }
    }

    protected function getAccount(int $id): Account
    {
        return Account::findOrFail($id);
    }

    /**
     * @return void
     * @throws ConnectionException
     * @throws RequestException
     */
    protected function authenticateApple(): void
    {
        $this->apple->appleId->accountManageToken()->throw();
        $this->apple->appleId->password($this->account->password)->throwUnlessStatus(204);
    }

    /**
     * @return Phone
     * @throws \Throwable
     */
    public function getPhone(): Phone
    {
        return $this->phone;
    }

    /**
     * @param Account $account
     * @return void
     */
    private function validateAccount(Account $account): void
    {
        if (!$account->password) {
            throw new \InvalidArgumentException("账号：{$account->account} 密码为空");
        }

        if ($account->bind_phone) {
            throw new \InvalidArgumentException("账号：{$account->account} 已绑定手机号");
        }
    }

    /**
     * @return void
     * @throws GuzzleException
     * @throws MaxRetryAttemptsException
     * @throws \Throwable
     */
    private function attemptBind(): void
    {
        for ($attempt = 1; $attempt <= $this->maxRetryAttempts; $attempt++) {
            try {
                // 获取可用的手机号码并修改号码的状态
                $this->phone = $this->getAvailablePhone();

                // 绑定手机号码
                $this->bindPhoneToAccount();

                // 绑定成功后更新账号状态
                $this->handleBindSuccess();
                return;
            } catch (BindPhoneCodeException|AttemptBindPhoneCodeException $e) {
                $this->handleBindException(exception: $e, attempt: $attempt);
            }
        }

        throw new MaxRetryAttemptsException(
            sprintf(
                "账号：%s 尝试 %d 次后绑定失败",
                $this->account->account,
                $this->maxRetryAttempts
            )
        );
    }

    /**
     * @return Phone
     * @throws \Throwable
     */
    private function getAvailablePhone(): Phone
    {
        return DB::transaction(function () {
            $phone = Phone::query()
                ->where('status', Phone::STATUS_NORMAL)
                ->whereNotNull(['phone_address', 'phone'])
                ->whereNotIn('id', $this->usedPhoneIds)
                ->lockForUpdate()
                ->firstOrFail();

            $phone->update(['status' => Phone::STATUS_BINDING]);

            return $phone;
        });
    }


    /**
     * @return Response
     * @throws ConnectionException
     * @throws RequestException
     */
    protected function sendBindRequest(): Response
    {
        //绑定手机号码
        $response = $this->apple->appleId->bindPhoneSecurityVerify(
            $this->phone ->national_number,
            $this->phone ->country_code,
            (string) $this->phone ->country_dial_code
        );

        // 验证手机验证码是否发生异常
        $response->throwIf(function ($re) use ($response) {

            if ($response->status() === 200 || $response->status() === 423){
                return false;
            }

            $error = $response->service_errors_first();
            // 骏证码无法发送至该电话号码。请稍后重试
            if ($error?->getCode() == -28248) {
                throw new BindPhoneCodeException(
                    "绑定失败 phone: {$this->phone ->phone} failed: {$error?->getMessage()} body: {$response->body()}", -28248
                );
            }

            $error = $response->validationErrorsFirst();
            if($error?->getCode() === 'phone.number.already.exists'){
                throw new BindPhoneCodeException(
                    "绑定失败 phone: {$this->phone ->phone} failed: {$error?->getMessage()} body: {$response->body()}", -28248
                );
            }

            throw new BindPhoneCodeException(
                "绑定失败 phone: {$this->phone->phone} body: {$response->body()}"
            );
        });

        return $response;
    }

    /**
     * @return void
     * @throws AttemptBindPhoneCodeException
     * @throws BindPhoneCodeException
     * @throws ConnectionException
     * @throws RequestException
     * @throws \Throwable
     */
    private function bindPhoneToAccount(): void
    {
        //发送绑定手机号码的请求
        $response = $this->sendBindRequest();

        // 获取号码 ID
        $id = $response->phoneNumberVerification()['phoneNumber']['id'] ?? null;
        if (empty($id)) {
            throw new BindPhoneCodeException(
                "绑定失败 phone: {$this->phone ->phone} 获取号码 ID 为空 body: {$response->body()}"
            );
        }

        // 这里循环获取手机验证码
        $code = $this->getPhoneCode();

        // 验证手机验证码
        $response = $this->apple->appleId->manageVerifyPhoneSecurityCode(
            id: $id,
            phoneNumber: $this->phone->national_number,
            countryCode: $this->phone->country_code,
            countryDialCode: (string)$this->phone->country_dial_code,
            code: $code
        );

        $response->throwIf(
            fn() => throw new BindPhoneCodeException(
                "绑定失败 phone: {$this->phone->phone} failed: {$response->service_errors_first()?->getMessage()} body: {$response->body()}"
            )
        );
    }

    /**
     * @return string
     * @throws ConnectionException|AttemptBindPhoneCodeException|ConnectionException
     */
    public function getPhoneCode(): string
    {
        sleep($this->phoneCodeWaitTime);

        return $this->apple->phoneCode->attemptGetPhoneCode(
            $this->phone->phone_address,
            $this->phone->phoneCodeParser(),
            $this->phoneCodeRetryAttempts,
            $this->phoneCodeRetryDelay
        );
    }


    /**
     * @return void
     * @throws \Throwable
     */
    protected function handleBindSuccess(): void
    {
        $this->logger->info("Account {$this->account->account} successfully bound to phone number {$this->phone->phone}");

        DB::transaction(function () {
            $this->account->update([
                'bind_phone'         => $this->phone->phone,
                'bind_phone_address' => $this->phone->phone_address,
            ]);
            $this->phone->update(['status' => Phone::STATUS_BOUND]);
        });

        Event::dispatch(
            new AccountBindPhoneSuccessEvent(account: $this->account, description:"账号： {$this->account->account} 绑定成功 手机号码：{$this->phone->phone}")
        );

        Notification::make()
            ->title("账号： {$this->account->account} 绑定成功 手机号码：{$this->phone->phone}")
            ->success()
            ->sendToDatabase(User::get());
    }



    protected function handlePhoneException(Throwable $exception): void
    {
        if (!$this->phone){
            return;
        }

        $status = $exception instanceof BindPhoneCodeException && $exception->getCode() == -28248
            ? Phone::STATUS_INVALID
            : Phone::STATUS_NORMAL;
        $this->phone->update(['status' => $status]);

        $this->usedPhoneIds[] = $this->phone->id;
    }

    protected function handleException(\Throwable $e): void
    {
        $this->logger->error("账号： {$this->account?->account} 绑定失败 {$e->getMessage()}");

        $this->handlePhoneException($e);

        $this->account && Event::dispatch(
            new AccountBindPhoneFailEvent(account: $this->account, description: "{$e->getMessage()}")
        );

        Notification::make()
            ->title("账号 {$this->account?->account} 绑定失败 {$e->getMessage()}")
            ->body($e->getMessage())
            ->warning()
            ->sendToDatabase(User::get());
    }

    protected function handleBindException(Exception $exception, int $attempt): void
    {
        $this->logger->error("绑定失败 (尝试 {$attempt}): {$exception->getMessage()}", [
            'account' => $this->account->account,
            'phone' => $this->phone->phone,
        ]);

        $this->handlePhoneException($exception);

        Event::dispatch(new AccountBindPhoneFailEvent(account: $this->account, description: $exception->getMessage()));
    }
}
