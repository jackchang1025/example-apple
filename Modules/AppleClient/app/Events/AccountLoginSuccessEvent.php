<?php

namespace Modules\AppleClient\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\AppleClient\Service\DataConstruct\Account;

class AccountLoginSuccessEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Account $account, public string $message = '登录成功')
    {
    }
}
