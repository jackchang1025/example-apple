<?php

namespace Modules\AppleClient\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\AppleClient\Service\DataConstruct\Account;

class AccountLoginFailEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Account $account, public string $message = '登陆失败')
    {
    }
}
