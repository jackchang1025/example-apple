<?php

namespace App\Jobs;

use App\Apple\Service\AccountBind;
use Apple\Client\AppleFactory;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

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
        return $this->id;
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
    public function __construct(protected readonly int $id, protected readonly string $clientId)
    {

    }

    /**
     * Execute the job.
     * @param AppleFactory $appleFactory
     * @return void
     */
    public function handle(AppleFactory $appleFactory): void
    {
        try {

            $accountBind = app(AccountBind::class,['apple'=>$appleFactory->create(clientId: $this->clientId,config: config('apple'))]);

            $accountBind->handle($this->id);

            // 任务成功执行，记录日志
            Log::info("BindAccountPhone job completed successfully", [
                'job_id' => $this->job->getJobId(),
                'account_id' => $this->id,
                'client_id' => $this->clientId
            ]);


        } catch (\Throwable $e) {

            Log::error("BindAccountPhone job failed", [
                'job_id' => $this->job->getJobId(),
                'account_id' => $this->id,
                'client_id' => $this->clientId,
                'error' => $e
            ]);
            $this->fail($e);
        }
    }
}
