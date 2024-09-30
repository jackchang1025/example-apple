<?php

namespace App\Apple\Service\Trait;

use App\Models\Account;

trait ValidateAccount
{
    /**
     * @param Account $account
     * @return void
     */
    protected function validateAccount(Account $account): void
    {
        if (!$account->password) {
            throw new \InvalidArgumentException("账号：{$account->account} 密码为空");
        }

        if ($account->bind_phone) {
            throw new \InvalidArgumentException("账号：{$account->account} 已绑定手机号");
        }
    }
}
