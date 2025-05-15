<?php

namespace App\Jobs;

use App\Services\AddSecurityVerifyPhoneService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use App\Models\Account;
class BindAccountPhone implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 作业可尝试的次数。
     *
     * @var int
     */
    public int $tries = 1;

    /**
     * 作业在超时前可以运行的秒数。
     *
     * @var int
     */
    public int $timeout = 60 * 30;

    /**
     * 唯一任务锁
     * @return string
     */
    public function uniqueId(): string
    {
        return $this->account->appleid;
    }

    /**
     * 指定一个超时时间，超过该时间任务不再保持唯一
     * @return int
     */
    public function uniqueFor(): int
    {
        return 3600; // 1 小时，单位为秒
    }

    /**
     * Create a new job instance.
     */
    public function __construct(protected readonly Account $account)
    {

    }


    public function handle(): void
    {
        try {

            $addSecurityVerifyPhoneService = new AddSecurityVerifyPhoneService($this->account);

            $addSecurityVerifyPhoneService->handle();

            // 任务成功执行，记录日志
            Log::info("BindAccountPhone job completed successfully", [
                'job_id' => $this->job->getJobId(),
                'account' => $this->account,
            ]);

        } catch (\Throwable $e) {

            Log::error("BindAccountPhone job failed", [
                'job_id' => $this->job->getJobId(),
                'account' => $this->account,
                'error' => $e
            ]);

            $this->fail($e);
        }
    }
}
