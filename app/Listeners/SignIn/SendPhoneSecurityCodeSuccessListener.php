<?php

namespace App\Listeners\SignIn;


use Weijiajia\SaloonphpAppleClient\Events\SendPhoneSecurityCodeSuccessEvent;

class SendPhoneSecurityCodeSuccessListener
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
    public function handle(SendPhoneSecurityCodeSuccessEvent $event): void
    {
        $account = \App\Models\Account::firstWhere('appleid',$event->appleId->appleId());

    }
}
