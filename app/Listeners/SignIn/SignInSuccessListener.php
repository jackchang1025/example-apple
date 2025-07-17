<?php

namespace App\Listeners\SignIn;

use App\Apple\Enums\AccountStatus;
use Weijiajia\SaloonphpAppleClient\Events\SignInSuccessEvent;

class SignInSuccessListener
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(SignInSuccessEvent $event): void
    {

        $appleId = \App\Models\Account::withTrashed()->where('appleid', $event->appleId->appleId())->first();

        if(!$appleId){
            return;
        }

        if($appleId->trashed()){
            $appleId->restore();
        }

        $appleId->status = AccountStatus::LOGIN_SUCCESS;
        $appleId->save();

    }
}
