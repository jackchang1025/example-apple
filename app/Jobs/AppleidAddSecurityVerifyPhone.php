<?php

namespace App\Jobs;

use App\Services\AddSecurityVerifyPhoneService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Account;
use Saloon\Exceptions\SaloonException;
use Saloon\Exceptions\Request\Statuses\UnauthorizedException;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Log;
use DateTime;
use Weijiajia\SaloonphpAppleClient\Exception\StolenDeviceProtectionException;
use App\Services\PhoneManager;
use App\Services\AuthenticationService;
use App\Services\PhoneVerificationService;

class AppleidAddSecurityVerifyPhone implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    /**
     * 作业在超时前可以运行的秒数。
     * 单次尝试的超时时间。
     * @var int
     */
    public int $timeout = 60 * 10;

    /**
     * 唯一任务锁
     * @return string
     */
    public function uniqueId(): string
    {
        return "appleid_add_security_verify_phone_lock_{$this->appleid->appleid}";
    }

    /**
     * 定义每次重试之间的延迟（秒）。
     * @return int|array
     */
    public function backoff(): int|array
    {
        return 60 * 10;
    }

    /**
     * 指定一个超时时间，超过该时间任务不再保持唯一
     * @return int
     */
    public function uniqueFor(): int
    {
        return 60 * 60; // 1小时，单位为秒
    }

    /**
     * Create a new job instance.
     */
    public function __construct(protected readonly Account $appleid)
    {
        $this->onQueue('appleid_add_security_verify_phone');
    }

    public function handle(PhoneManager $phoneManager, AuthenticationService $authenticationService): void
    {
        try {

            Log::info("[BindAccountPhone] handle", ['appleid' => $this->appleid->appleid]);

            // 执行绑定服务
            $bindingService = $this->createBindingService($phoneManager, $authenticationService);
            $bindingService->handle();

            Log::info("[BindAccountPhone] Successfully bound phone for account {$this->appleid->appleid} on attempt {$this->attempts()}.");
        } catch (\Throwable $e) {

            Log::error("[BindAccountPhone] Error binding phone for account {$this->appleid->appleid} on attempt {$this->attempts()}: {$e}");

            // 处理异常，决定是否重试
            $this->handleException($e);
        }
    }

    /**
     * 创建绑定服务实例
     * 这个方法可以在测试中被覆盖，方便进行单元测试
     *
     * @param PhoneManager $phoneManager
     * @param AuthenticationService $authenticationService
     * @return AddSecurityVerifyPhoneService
     */
    protected function createBindingService(PhoneManager $phoneManager, AuthenticationService $authenticationService): AddSecurityVerifyPhoneService
    {
        // 为这个账号创建专用的PhoneVerificationService实例
        $accountSpecificVerificationService = app()->make(PhoneVerificationService::class, ['account' => $this->appleid]);

        return new AddSecurityVerifyPhoneService($this->appleid, $phoneManager, $authenticationService, $accountSpecificVerificationService);
    }


    /**
     * 处理异常，决定重试策略
     */
    private function handleException(\Throwable $e): void
    {
        $shouldRetry = $this->shouldRetryOnException($e);
        if (!$shouldRetry) {
            return;
        }

        throw $e;
    }

    /**
     * 根据异常类型决定是否应该重试
     */
    private function shouldRetryOnException(\Throwable $e): bool
    {
        // 对于以下异常类型不重试
        if (
            $e instanceof UnauthorizedException ||
            $e instanceof StolenDeviceProtectionException
        ) {
            return false;
        }

        // 对于Saloon异常，除了401以外都重试
        if ($e instanceof SaloonException) {
            return true;
        }

        // 其他异常默认不重试（由事件监听器处理状态）
        return false;
    }
}
