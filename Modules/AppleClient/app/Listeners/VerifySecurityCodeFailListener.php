<?php

namespace Modules\AppleClient\Listeners;

use App\Apple\Enums\AccountStatus;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\AppleClient\Events\verifySecurityCodeFailEvent;

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
    public function handle(verifySecurityCodeFailEvent $event): void
    {
        $model = $event->account->model();
        $model->update(['status' => AccountStatus::AUTH_FAIL]);

        $model->logs()->create(['action' => '双重认证', 'description' => $event->message]);
    }
}
