<?php

namespace App\Listeners\SecurityPhone;


use Weijiajia\SaloonphpAppleClient\Events\SecurityPhone\SendPhoneSecurityCodeSuccessEvent;

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
