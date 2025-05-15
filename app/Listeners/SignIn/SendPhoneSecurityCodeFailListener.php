<?php

namespace App\Listeners\SignIn;

use Weijiajia\SaloonphpAppleClient\Events\SendPhoneSecurityCodeFailedEvent;

class SendPhoneSecurityCodeFailListener
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
    public function handle(SendPhoneSecurityCodeFailedEvent $event): void
    {
        $account = \App\Models\Account::firstWhere('appleid',$event->appleId->appleId());

    }
}
