<?php

namespace App\Events;

use App\Apple\Enums\AccountStatus;
use App\Models\Account;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountLoginSuccessEvent extends AccountStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(Account $account,string $action = '登录成功', string $description = '登录成功')
    {
        parent::__construct($account, AccountStatus::LOGIN_SUCCESS,$action,$description);
    }


}
