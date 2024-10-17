<?php

namespace App\Jobs;

use App\Models\Account;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\AppleClient\Service\AppleClientFactory;
use Modules\AppleClient\Service\AppleClientService;
use Modules\AppleClient\Service\ProcessAccountImportService;

class ProcessAccountImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Account $account)
    {
        $this->onQueue('account-processing');
    }


    public function handle(AppleClientFactory $appleClientFactory): void
    {
        Log::info('开始处理账号数据', ['account' => $this->account->toArray()]);

        $client             = $appleClientFactory->create($this->account);
        $appleClientService = app(AppleClientService::class, ['client' => $client]);

        $processAccountImportService = new ProcessAccountImportService($this->account, $appleClientService);

        $processAccountImportService->handle();

        // 执行账号处理逻辑
        // ...
    }
}
