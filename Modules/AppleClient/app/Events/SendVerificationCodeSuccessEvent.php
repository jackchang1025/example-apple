<?php

namespace Modules\AppleClient\Events;

use Modules\AppleClient\Service\DataConstruct\Account;

class SendVerificationCodeSuccessEvent
{
    public function __construct(public Account $account, public string $message = '发送设备验证码成功')
    {
    }
}
