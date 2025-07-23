<?php

namespace App\Services;

use App\Models\Account;

/**
 * 认证服务
 * 负责处理Apple ID认证相关的逻辑
 */
class AuthenticationService
{
    /**
     * 确保账号认证有效
     *
     * @param Account $account
     * @return void
     */
    public function ensureAuthenticated(Account $account): void
    {
        $cookieJar = $account->cookieJar();

        if ($cookieJar instanceof \GuzzleHttp\Cookie\FileCookieJar) {
            
            // 检查认证是否有效，如果无效则重新获取token
            if (!$cookieJar || !$this->isAuthenticationValid($cookieJar)) {
                $this->refreshAuthentication($account);
            }
        }
    }

    /**
     * 刷新认证
     *
     * @param Account $account
     * @return void
     */
    public function refreshAuthentication(Account $account): void
    {
        $account->appleIdResource()->getAccountManagerResource()->token();
        $account->syncCookies();
    }

    /**
     * 检查认证是否有效
     *
     * @param mixed $cookieJar
     * @return bool
     */
    private function isAuthenticationValid(\GuzzleHttp\Cookie\FileCookieJar $cookieJar): bool
    {
        $awat = $cookieJar->getCookieByName('awat');

        return $awat && !$awat->isExpired();
    }

    /**
     * 验证账号状态
     *
     * @param Account $account
     * @return bool
     */
    public function isAccountValid(Account $account): bool
    {
        try {
            $this->ensureAuthenticated($account);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

}
