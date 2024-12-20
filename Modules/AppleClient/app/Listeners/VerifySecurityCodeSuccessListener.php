<?php

namespace Modules\AppleClient\Listeners;

use App\Apple\Enums\AccountStatus;
use Modules\AppleClient\Events\verifySecurityCodeSuccessEvent;

class VerifySecurityCodeSuccessListener
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
    public function handle(VerifySecurityCodeSuccessEvent $event): void
    {
        $model = $event->account->model();
        $model->update(['status' => AccountStatus::AUTH_SUCCESS]);

        $model->logs()->create(['action' => '双重认证', 'description' => "验证码：{$event->code} 验证成功"]);
    }
}
