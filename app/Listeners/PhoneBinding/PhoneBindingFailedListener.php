<?php

namespace App\Listeners\PhoneBinding;

use App\Apple\Enums\AccountStatus;
use App\Events\PhoneBinding\PhoneBindingFailed;
use Illuminate\Support\Facades\Log;
use Weijiajia\SaloonphpAppleClient\Exception\StolenDeviceProtectionException;
use Saloon\Exceptions\Request\Statuses\UnauthorizedException;
use App\Filament\Resources\AccountResource\Pages\ViewAccount;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\Trait\TruncatesNotificationMessage;
use Saloon\Exceptions\SaloonException;

/**
 * 记录手机号绑定失败日志
 */
class PhoneBindingFailedListener
{
    use TruncatesNotificationMessage;

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
            ->title(sprintf('%s 添加授权号码失败', $event->account->appleid))
            ->body($this->getSafeExceptionMessage($event->exception))
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
            // 不会重试的异常，设置为最终失败状态
            $exception instanceof StolenDeviceProtectionException => AccountStatus::THEFT_PROTECTION,
            $exception instanceof UnauthorizedException => AccountStatus::BIND_FAIL,
            $exception instanceof SaloonException => AccountStatus::BIND_RETRY,
            // 其他异常默认为失败
            default => AccountStatus::BIND_FAIL,
        };
    }
}
