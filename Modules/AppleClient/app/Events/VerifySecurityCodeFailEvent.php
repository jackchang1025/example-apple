<?php

namespace Modules\AppleClient\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\DataConstruct\PhoneNumber;

class VerifySecurityCodeFailEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Account $account,
        public string $code,
        public ?PhoneNumber $phone = null,
        public string $message = '验证码错误'
    ) {
    }
}
