<?php

namespace Modules\AppleClient\Events;

use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\DataConstruct\PhoneNumber;

class SendPhoneSecurityCodeSuccessEvent
{
    public function __construct(
        public Account $account,
        public PhoneNumber $phoneNumber,
        public string $message = '验证码已发送至您的手机'
    ) {
    }
}
