<?php

namespace Modules\AppleClient\Events;

use App\Apple\Enums\AccountStatus;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\DataConstruct\AddSecurityVerifyPhone\AddSecurityVerifyPhoneInterface;

class AccountBindPhoneSuccessEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Account $account,
        public AddSecurityVerifyPhoneInterface $addSecurityVerifyPhone,
        public string $message = '绑定成功'
    )
    {

    }


}
