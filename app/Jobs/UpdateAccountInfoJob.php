<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Account;
use App\Services\UpdateAccountInfoService;
use Throwable;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use App\Filament\Resources\AccountResource\Pages\ViewAccount;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\AuthenticationService;

class UpdateAccountInfoJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     */
    public function __construct(protected readonly Account $apple)
    {
        $this->onQueue('update_account_info');
    }

    /**
     * 唯一任务锁
     * @return string
     */
    public function uniqueId(): string
    {
        return "update_account_info_lock_{$this->apple->appleid}";
    }

    /**
     * 重试次数
     * @var int
     */
    public int $tries = 1;

    /**
     * Execute the job.
     */
    public function handle(AuthenticationService $authService): void
    {
        try {

            $updateService = $this->createUpdateService($authService);
            $updateService->handle();
        } catch (Throwable $e) {
            // The service already logged the error, so we just re-throw
            // to fail the job and stop the chain.

            Notification::make()
                ->title("获取用户信息失败")
                ->body($e->getMessage())
                ->warning()
                ->actions([
                    Action::make('view')
                        ->label('查看账户')
                        ->button()
                        ->url(ViewAccount::getUrl(['record' => $this->apple->id]), shouldOpenInNewTab: true),
                ])
                ->sendToDatabase(Auth::user() ?? User::all());

            Log::error("UpdateAccountInfoService failed for account {$this->apple->appleid}: {$e->getMessage()}");
        }
    }

    /**
     * 创建更新服务实例
     * 这个方法可以在测试中被覆盖，方便进行单元测试
     *
     * @param AuthenticationService $authService
     * @return UpdateAccountInfoService
     */
    protected function createUpdateService(AuthenticationService $authService): UpdateAccountInfoService
    {
        return new UpdateAccountInfoService($this->apple, $authService);
    }
}
