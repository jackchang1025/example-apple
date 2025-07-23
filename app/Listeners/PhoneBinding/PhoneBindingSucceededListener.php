<?php

namespace App\Listeners\PhoneBinding;

use App\Apple\Enums\AccountStatus;
use App\Events\PhoneBinding\PhoneBindingSucceeded;
use Illuminate\Support\Facades\Log;
use App\Filament\Resources\AccountResource\Pages\ViewAccount;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

/**
 * 记录手机号绑定成功日志
 */
class PhoneBindingSucceededListener
{
    /**
     * Handle the event.
     */
    public function handle(PhoneBindingSucceeded $event): void
    {
        // 更新账号状态和绑定信息
        $event->account->update([
            'status' => AccountStatus::BIND_SUCCESS,
            'bind_phone' => $event->phone->phone,
            'bind_phone_address' => $event->phone->phone_address,
        ]);


        // 记录账号日志
        $event->account->logs()->create([
            'action' => '添加授权号码成功',
            'request' => [
                'phone' => $event->phone->phone,
                'phone_address' => $event->phone->phone_address,
                'attempt' => $event->attempt,
                'timestamp' => now()->toISOString(),
            ]
        ]);

        Notification::make()
                ->title('添加授权号码成功')
                ->body($this->formatSuccessMessage($event))
                ->success()
                ->actions([
                    Action::make('view')
                        ->label('查看账户')
                        ->button()
                        ->url(ViewAccount::getUrl(['record' => $event->account->id]), shouldOpenInNewTab: true),
                ])
                ->sendToDatabase(User::first());

        // 记录系统日志
        Log::info('添加授权号码成功', [
            'account_id' => $event->account->id,
            'appleid' => $event->account->appleid,
            'phone' => $event->phone->phone,
            'attempt' => $event->attempt,
        ]);
    }

    private function formatSuccessMessage(PhoneBindingSucceeded $event): string
    {
        return sprintf(
            "次数: %d 账号：%s 手机号码: %s 绑定成功",
            $event->attempt,
            $event->account->appleid,
            $event->phone->phone
        );
    }
}
