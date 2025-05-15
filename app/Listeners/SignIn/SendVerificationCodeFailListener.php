<?php

namespace App\Listeners\SignIn;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Weijiajia\SaloonphpAppleClient\Events\SendVerificationCodeFailedEvent;

class SendVerificationCodeFailListener
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
    public function handle(SendVerificationCodeFailedEvent $event): void
    {
        $account = \App\Models\Account::firstWhere('appleid',$event->appleId->appleId());

    }
}
