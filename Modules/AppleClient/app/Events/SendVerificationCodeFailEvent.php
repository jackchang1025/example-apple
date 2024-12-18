<?php

namespace Modules\AppleClient\Events;

use Modules\AppleClient\Service\DataConstruct\Account;

class SendVerificationCodeFailEvent
{
    public function __construct(public Account $account, public string $message = '发送设备验证码失败')
    {
    }
}
