<?php

namespace App\Listeners\PhoneBinding;

use App\Apple\Enums\AccountStatus;
use App\Events\PhoneBinding\PhoneBindingStarted;
use Illuminate\Support\Facades\Log;

/**
 * 记录手机号绑定开始日志
 */
class PhoneBindingStartedListener
{
    /**
     * Handle the event.
     */
    public function handle(PhoneBindingStarted $event): void
    {
        $event->account->update(['status' => AccountStatus::BIND_ING]);       
    }
}
