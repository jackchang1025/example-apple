<?php

namespace App\Listeners\SignIn;

use App\Apple\Enums\AccountStatus;
use Illuminate\Support\Facades\Cache;
use Weijiajia\SaloonphpAppleClient\Events\SignInSuccessEvent;

class SignInSuccessListener
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
    public function handle(SignInSuccessEvent $event): void
    {
        $account = \App\Models\Account::updateOrCreate(['appleid' => $event->appleId->appleId()], [
            'password' => $event->appleId->password(),
            'status'   => AccountStatus::LOGIN_SUCCESS,
        ]);

    }
}
