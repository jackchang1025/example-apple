<?php

namespace App\Listeners\SignIn;

use App\Apple\Enums\AccountStatus;
use Weijiajia\SaloonphpAppleClient\Events\SignInFailedEvent;
use App\Models\Account;
class SignInFailedListener
{
    /**
     * Handle the event.
     */
    public function handle(SignInFailedEvent $event): void
    {
        $apple = Account::where('appleid', $event->appleId->appleId())->first();

        if(!$apple){
            return;
        }

        if($apple->status === null){
            $apple->delete();
            return;
        }

        $apple->status = AccountStatus::LOGIN_FAIL;
        $apple->save();
       
    }
}
