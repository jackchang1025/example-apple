<?php

namespace Modules\AppleClient\Listeners;

use App\Apple\Enums\AccountStatus;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\AppleClient\Events\AccountBindPhoneFailEvent;

class AccountBindPhoneFailListener
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
    public function handle(AccountBindPhoneFailEvent $event): void
    {
        $model = $event->account->model();
        $model->update(['status' => AccountStatus::BIND_FAIL]);

        $model->logs()->create(['action' => '添加授权号码', 'description' => $event->message]);
    }
}
