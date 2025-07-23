<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\UpdateAccountInfoFailed;
use App\Filament\Resources\AccountResource\Pages\ViewAccount;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
class UpdateAccountInfoFailedListener
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
    public function handle(UpdateAccountInfoFailed $event): void
    {
        Notification::make()
        ->title("{$event->appleId->appleid} 更新{$event->type}失败")
        ->body($event->e->getMessage())
        ->danger()
        ->actions([
            Action::make('view')
                ->label('查看账户')
                ->button()
                ->url(ViewAccount::getUrl(['record' => $event->appleId->id]), shouldOpenInNewTab: true),
        ])
        ->sendToDatabase(User::first());

        Log::error("{$event->appleId->appleid} 更新{$event->type}失败",[
            'appleid' => $event->appleId->appleid,
            'error' => $event->e->getMessage(),
            'trace' => $event->e->getTraceAsString(),
        ]);
    }
}
