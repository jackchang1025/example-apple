<?php

namespace App\Events;

use App\Apple\Enums\AccountStatus;
use App\Models\Account;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountAuthFailEvent extends AccountStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(Account $account,string $action = '验证失败', string $description = '验证码不正确')
    {
        parent::__construct($account, AccountStatus::AUTH_FAIL,$action,$description);
    }
}
