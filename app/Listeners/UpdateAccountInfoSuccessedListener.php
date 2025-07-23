<?php

namespace App\Listeners;

use App\Filament\Resources\AccountResource\Pages\ViewAccount;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use App\Events\UpdateAccountInfoSuccessed;
class UpdateAccountInfoSuccessedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UpdateAccountInfoSuccessed $event): void
    {
        // Notification::make()
        // ->title('更新账户信息成功')
        // ->body("{$event->appleId->appleid} 更新{$event->type}成功")
        // ->success()
        // ->actions([
        //     Action::make('view')
        //         ->label('查看账户')
        //         ->button()
        //         ->url(ViewAccount::getUrl(['record' => $event->appleId->id]), shouldOpenInNewTab: true),
        // ])
        // ->sendToDatabase(User::first());
    }
}
