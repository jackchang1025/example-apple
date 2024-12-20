<?php

namespace Modules\AppleClient\Listeners;

use App\Apple\Enums\AccountStatus;
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

        $model->logs()->create(['action' => '双重认证', 'description' => "验证码：{$event->code} 验证失败"]);
    }
}
