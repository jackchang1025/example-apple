<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PhoneValidationService;
use Illuminate\Console\Command;

/**
 * 手机号验证命令
 */
class ValidatePhoneNumbersCommand extends Command
{
    protected $signature = 'phone:validate {--force : 验证所有手机号，包括已失效的} {--concurrency=100 : 并发请求数量}';

    protected $description = '验证手机号码是否失效并更新状态';

    public function handle(PhoneValidationService $validationService): int
    {
        $force = $this->option('force');
        $concurrency = (int) $this->option('concurrency');

        $this->info('🔍 开始验证手机号码...');
        $this->line('验证模式: ' . ($force ? '验证所有手机号' : '仅验证正常状态'));
        $this->line("并发数: {$concurrency}");

        $query = $validationService->getValidatablePhones($force);
        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info('✅ 没有需要验证的手机号码');
            return Command::SUCCESS;
        }

        $this->line("找到 {$totalCount} 个需要验证的手机号");

        return $this->processValidationConcurrently($validationService, $query, $totalCount, $concurrency);
    }

    private function processValidationConcurrently(
        PhoneValidationService $validationService,
        $query,
        int $totalCount,
        int $concurrency
    ): int {
        $this->info('🚀 使用并发验证，性能大幅提升...');

        $startTime = microtime(true);
        $batchSize = 500; // 每批处理500个，避免内存过载
        $totalInvalid = 0;
        $totalValid = 0;
        $totalErrors = 0;
        $processed = 0;

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->setFormat('%current%/%max% [%bar%] %percent:3s%% - 已处理: %message%');

        $query->chunk($batchSize, function ($phones) use (
            $validationService,
            $concurrency,
            $progressBar,
            &$totalInvalid,
            &$totalValid,
            &$totalErrors,
            &$processed
        ) {
            // 使用并发验证
            $results = $validationService->validatePhonesConcurrently($phones, (int) $concurrency);

            $totalInvalid += $results['invalid_count'];
            $totalValid += $results['valid_count'];
            $totalErrors += $results['error_count'];
            $processed += $results['total'];

            $progressBar->setMessage("{$processed} 条记录");
            $progressBar->advance($results['total']);
        });

        $progressBar->finish();
        $this->newLine(2);

        $this->displayResults($totalCount, $totalValid, $totalInvalid, $totalErrors, microtime(true) - $startTime);

        return Command::SUCCESS;
    }

    private function displayResults(int $total, int $valid, int $invalid, int $errors, float $duration): void
    {
        $this->info('✅ 并发验证完成！');
        $this->line("总数: {$total} | 有效: {$valid} | 失效: {$invalid} | 错误: {$errors}");
        $this->line("耗时: " . round($duration, 2) . " 秒");

        if ($total > 0) {
            $this->line("平均速度: " . round($total / $duration, 2) . " 条/秒");
        }

        $this->newLine();
        $this->info('🎉 任务完成，性能提升显著！');
    }
}
