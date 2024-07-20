<?php

namespace App\Jobs;

use App\Apple\Service\AccountBind;
use App\Apple\Service\AppleFactory;
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
     */
    public function handle(AppleFactory $appleFactory,LoggerInterface $logger): void
    {
        try {

            $apple = $appleFactory->create($this->clientId);

            $accountBind = new AccountBind($apple,$logger);

            $accountBind->handle($this->id);
        } catch (\Exception $e) {
            Log::error("BindAccountPhone job:{$this->id} {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
