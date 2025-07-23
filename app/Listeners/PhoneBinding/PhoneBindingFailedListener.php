<?php

namespace App\Listeners\PhoneBinding;

use App\Apple\Enums\AccountStatus;
use App\Events\PhoneBinding\PhoneBindingFailed;
use Illuminate\Support\Facades\Log;
use Weijiajia\SaloonphpAppleClient\Exception\StolenDeviceProtectionException;
use App\Filament\Resources\AccountResource\Pages\ViewAccount;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

/**
 * 记录手机号绑定失败日志
 */
class PhoneBindingFailedListener
{
    /**
     * Handle the event.
     */
    public function handle(PhoneBindingFailed $event): void
    {
        // 根据异常类型设置状态
        $status = $this->determineAccountStatus($event->exception);
        $event->account->update(['status' => $status]);


        // 记录账号日志
        $event->account->logs()->create([
            'action' => '添加授权号码失败',
            'request' => [
                'phone' => $event->phone?->phone,
                'attempt' => $event->attempt,
                'message' => $event->exception->getMessage(),
                'trace' => $event->exception->getTraceAsString(),
            ]
        ]);

        Notification::make()
        ->title('添加授权号码失败')
        ->body($event->exception->getMessage())
        ->warning()
        ->actions([
            Action::make('view')
                ->label('查看账户')
                ->button()
                ->url(ViewAccount::getUrl(['record' => $event->account->id]), shouldOpenInNewTab: true),
        ])
        ->sendToDatabase(User::first());
        
        // 记录系统日志
        Log::error('添加授权号码失败', [
            'account_id' => $event->account->id,
            'appleid' => $event->account->appleid,
            'phone' => $event->phone?->phone,
            'attempt' => $event->attempt,
            'exception' => $event->exception->getMessage(),
            'exception_class' => get_class($event->exception),
        ]);

        
    }

    /**
     * 根据异常类型确定账号状态
     */
    private function determineAccountStatus(\Throwable $exception): AccountStatus
    {
        return match (true) {
            $exception instanceof StolenDeviceProtectionException => AccountStatus::THEFT_PROTECTION,
            default => AccountStatus::BIND_FAIL,
        };
    }
}
