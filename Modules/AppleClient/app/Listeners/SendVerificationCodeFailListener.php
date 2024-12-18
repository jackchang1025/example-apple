<?php

namespace Modules\AppleClient\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\AppleClient\Events\SendVerificationCodeFailEvent;

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
    public function handle(SendVerificationCodeFailEvent $event): void
    {
        $model = $event->account->model();

        $model->logs()->create(['action' => '发送设备验证码', 'description' => $event->message]);
    }
}
