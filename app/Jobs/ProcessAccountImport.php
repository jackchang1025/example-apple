<?php

namespace App\Jobs;

use App\Models\Account;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\AppleClient\Service\AppleAccountManagerFactory;
use Modules\AppleClient\Service\AppleAccountManager;
use Modules\AppleClient\Service\ProcessAccountImportService;
use Psr\Log\LoggerInterface;

class ProcessAccountImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Account $account)
    {
        $this->onQueue('account-processing');
    }


    public function handle(AppleAccountManagerFactory $accountManagerFactory, LoggerInterface $logger): void
    {
        Log::info('开始处理账号数据', ['account' => $this->account->toArray()]);

        try {

            $accountManager              = $accountManagerFactory->create($this->account);
            $processAccountImportService = new ProcessAccountImportService($accountManager);
            $processAccountImportService->withLogger($logger);
            $processAccountImportService->handle();

        } catch (\Exception $e) {

            Log::error('处理账号数据失败', [
                'account' => $this->account->toArray(),
                'message' => $e,
            ]);

            $this->fail($e);
        }

        // 执行账号处理逻辑
        // ...
    }
}
