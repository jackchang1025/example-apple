<?php

namespace App\Listeners\SecurityPhone;

use Weijiajia\SaloonphpAppleClient\Events\SecurityPhone\SendPhoneSecurityCodeFailedEvent;

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
