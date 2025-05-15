<?php

namespace App\Listeners\SignIn;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Weijiajia\SaloonphpAppleClient\Events\SendVerificationCodeSuccessEvent;

class SendVerificationCodeSuccessListener
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
    public function handle(SendVerificationCodeSuccessEvent $event): void
    {
        $account = \App\Models\Account::firstWhere('appleid',$event->appleId->appleId());

    }
}
