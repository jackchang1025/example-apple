<?php

namespace App\Listeners\SignIn;

use App\Apple\Enums\AccountStatus;
use Weijiajia\SaloonphpAppleClient\Events\SignInFailedEvent;

class SignInFailedListener
{
    /**
     * Handle the event.
     */
    public function handle(SignInFailedEvent $event): void
    {
         \App\Models\Account::where('appleid', $event->appleId->appleId())->update([
            'status' => AccountStatus::LOGIN_FAIL,
        ]);
       
    }
}
