<?php

namespace Modules\AppleClient\Events;

use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\DataConstruct\PhoneNumber;

class SendPhoneSecurityCodeFailEvent
{
    public function __construct(
        public Account $account,
        public PhoneNumber $phoneNumber,
        public string $message = '发动手机验证码失败'
    ) {
    }
}
