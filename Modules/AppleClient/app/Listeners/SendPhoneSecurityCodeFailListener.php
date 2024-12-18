<?php

namespace Modules\AppleClient\Listeners;

use App\Apple\Enums\AccountStatus;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\AppleClient\Events\SendPhoneSecurityCodeFailEvent;

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
    public function handle(SendPhoneSecurityCodeFailEvent $event): void
    {
        $model = $event->account->model();

        $model->logs()->create(['action' => '发送手机验证码', 'description' => $event->message]);
    }
}
