<?php

namespace App\Listeners\SignIn;

use App\Apple\Enums\AccountStatus;
use Weijiajia\SaloonphpAppleClient\Events\VerifySecurityCodeFailedEvent;

class VerifySecurityCodeFailListener
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
    public function handle(verifySecurityCodeFailedEvent $event): void
    {
        $account = \App\Models\Account::firstWhere('appleid',$event->appleId->appleId());
        $account?->update(['status' => AccountStatus::AUTH_FAIL]);

    }
}
