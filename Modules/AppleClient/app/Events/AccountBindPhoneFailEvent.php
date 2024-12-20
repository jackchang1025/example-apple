<?php

namespace Modules\AppleClient\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\DataConstruct\AddSecurityVerifyPhone\AddSecurityVerifyPhoneInterface;

class AccountBindPhoneFailEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Account $account,
        public ?AddSecurityVerifyPhoneInterface $addSecurityVerifyPhone = null,
        public string $message = '绑定失败'
    )
    {
    }
}
