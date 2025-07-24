<?php

namespace App\Services;

use App\Apple\Enums\AccountStatus;
use App\Events\PhoneBinding\PhoneBindingFailed;
use App\Events\PhoneBinding\PhoneBindingStarted;
use App\Events\PhoneBinding\PhoneBindingSucceeded;
use App\Models\Account;
use App\Models\Phone;
use Throwable;
use Weijiajia\SaloonphpAppleClient\Exception\MaxRetryAttemptsException;
use Weijiajia\SaloonphpAppleClient\Exception\Phone\PhoneException;
use Weijiajia\SaloonphpAppleClient\Exception\Phone\PhoneNumberAlreadyExistsException;
use Weijiajia\SaloonphpAppleClient\Exception\StolenDeviceProtectionException;
use Weijiajia\SaloonphpAppleClient\Exception\VerificationCodeException;
use Weijiajia\SaloonphpAppleClient\Exception\VerificationCodeSentTooManyTimesException;
use App\Services\Integrations\Phone\Exception\AttemptGetPhoneCodeException;
use App\Services\Integrations\Phone\Exception\InvalidPhoneException;

/**
 * 手机号绑定服务
 * 负责协调整个手机号绑定流程
 */
class AddSecurityVerifyPhoneService
{
    private const MAX_BIND_ATTEMPTS = 5;

    /**
     * @var Phone|null 当前正在处理的手机号对象
     */
    private ?Phone $currentPhone = null;

    /**
     * @var int 当前尝试次数
     */
    private int $currentAttempt = 1;

    public function __construct(
        private readonly Account $account,
        private readonly PhoneManager $phoneManager,
        private readonly AuthenticationService $authService,
        private readonly PhoneVerificationService $phoneVerificationService
    ) {}

    /**
     * 执行手机号绑定流程
     *
     * @return void
     * @throws Throwable
     */
    public function handle(): void
    {
        if ($this->shouldSkipBinding()) {
            return;
        }

        // 触发绑定开始事件
        event(new PhoneBindingStarted($this->account));

        try {

            $this->authService->ensureAuthenticated($this->account);

            $this->executeBindingProcess();
        } catch (Throwable $e) {
            $this->handleBindingFailure($e);
            throw $e;
        }
    }

    /**
     * 检查是否应该跳过绑定
     *
     * @return bool
     */
    private function shouldSkipBinding(): bool
    {
        $this->account->refresh();

        return !$this->account
            || $this->account->status === AccountStatus::BIND_SUCCESS
            || $this->account->bind_phone
            || $this->account->status === AccountStatus::BIND_ING;
    }

    /**
     * 执行绑定流程
     *
     * @return void
     * @throws MaxRetryAttemptsException
     * @throws Throwable
     */
    private function executeBindingProcess(): void
    {
        for ($this->currentAttempt = 1; $this->currentAttempt <= self::MAX_BIND_ATTEMPTS; $this->currentAttempt++) {
            try {
                $this->currentPhone = $this->phoneManager->getAvailablePhone();

                $this->phoneVerificationService->verify($this->currentPhone);

                $this->handleBindingSuccess();
                return;
            } catch (
                VerificationCodeException 
                | VerificationCodeSentTooManyTimesException 
                | PhoneNumberAlreadyExistsException 
                | PhoneException 
                | AttemptGetPhoneCodeException 
                | InvalidPhoneException $e
            ) {
                $this->handlePhoneException($e);
            } catch (StolenDeviceProtectionException $e) {
                $this->handlePhoneException($e);
                throw $e;
            } catch (Throwable $e) {
                $this->handlePhoneException($e);
                throw $e;
            }
        }

        throw new MaxRetryAttemptsException('达到最大重试次数，绑定失败。');
    }

    /**
     * 处理绑定成功
     *
     * @return void
     */
    private function handleBindingSuccess(): void
    {
        $this->phoneManager->markPhoneAsBound($this->currentPhone);
        event(new PhoneBindingSucceeded($this->account, $this->currentPhone, $this->currentAttempt));
    }

    /**
     * 处理手机异常
     *
     * @param Throwable $exception
     * @return void
     */
    private function handlePhoneException(Throwable $exception): void
    {
        if ($this->currentPhone) {
            $this->phoneManager->handlePhoneException($this->currentPhone, $exception);
        }
    }

    /**
     * 处理绑定失败
     *
     * @param Throwable $exception
     * @return void
     */
    private function handleBindingFailure(Throwable $exception): void
    {
        event(new PhoneBindingFailed($this->account, $exception, $this->currentAttempt, $this->currentPhone));
    }

    /**
     * 获取当前账号
     *
     * @return Account
     */
    public function getAccount(): Account
    {
        return $this->account;
    }

    /**
     * 获取当前手机号
     *
     * @return Phone|null
     */
    public function getCurrentPhone(): ?Phone
    {
        return $this->currentPhone;
    }

    /**
     * 获取当前尝试次数
     *
     * @return int
     */
    public function getCurrentAttempt(): int
    {
        return $this->currentAttempt;
    }
}
