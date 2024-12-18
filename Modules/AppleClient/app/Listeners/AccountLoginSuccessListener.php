<?php

namespace Modules\AppleClient\Listeners;

use App\Apple\Enums\AccountStatus;
use Illuminate\Support\Facades\Cache;
use Modules\AppleClient\Events\AccountLoginSuccessEvent;

class AccountLoginSuccessListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     */
    public function handle(AccountLoginSuccessEvent $event): void
    {
        $account = \App\Models\Account::updateOrCreate(['account' => $event->account->getAccount()], [
            'password' => $event->account->getPassword(),
            'status'   => AccountStatus::LOGIN_SUCCESS,
        ]);

        $account->logs()->create(['action' => '登陆', 'description' => $event->message]);

        Cache::put($event->account->getSessionId(), $event->account);
    }
}
