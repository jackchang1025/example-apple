<?php

namespace App\Jobs;

use App\Apple\Service\AccountBind;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BindAccountPhone implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 作业可尝试的次数。
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * 作业在超时前可以运行的秒数。
     *
     * @var int
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(protected readonly int $id,protected readonly AccountBind $accountBind)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->accountBind->handle($this->id);
    }
}
