<?php

namespace App\Jobs;

use Filament\Actions\Imports\Jobs\ImportCsv;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use libphonenumber\NumberParseException;

class ImportCsvJob extends ImportCsv implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务可以尝试的次数。
     *
     * @var int
     */
    public int $tries = 1;

    /**
     * 允许在失败前的最大未处理异常数。
     *
     * @var int
     */
    public int $maxExceptions = 1;

    /**
     * 执行作业。
     *
     * @return void
     */
    public function handle(): void
    {
        try {

            parent::handle();

        } catch (NumberParseException $e) {
            // 对于验证错误，我们直接标记作业为失败，而不进行重试
            $this->fail($e);
        }
    }

    /**
     * 处理作业失败的情况。
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        // 记录错误
        \Log::error('Phone import failed: ' . $exception->getMessage());

        // 如果是验证错误，更新导入记录的状态

    }
}
