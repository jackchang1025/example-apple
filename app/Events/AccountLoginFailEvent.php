<?php

namespace App\Events;

use App\Apple\Enums\AccountStatus;
use App\Models\Account;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountLoginFailEvent extends AccountStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(Account $account,string $action = '登录失败', string $description = '登录失败')
    {
        parent::__construct($account, AccountStatus::LOGIN_FAIL,$action, $description);
    }


}
