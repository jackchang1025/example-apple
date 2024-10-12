<?php

namespace App\Events;

use App\Apple\Enums\AccountStatus;
use App\Models\Account;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountAuthSuccessEvent extends AccountStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(Account $account,string $action = '验证成功', string $description = '验证成功')
    {
        parent::__construct($account, AccountStatus::AUTH_SUCCESS,$action,$description);
    }


}
