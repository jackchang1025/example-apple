<?php

namespace App\Events;

use App\Apple\Enums\AccountStatus;
use App\Models\Account;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountBindPhoneSuccessEvent extends AccountStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(Account $account,string $action = '绑定成功', string $description = '绑定手机号码成功')
    {
        parent::__construct($account, AccountStatus::BIND_SUCCESS,$action,$description);
    }


}
