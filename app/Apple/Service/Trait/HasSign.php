<?php

namespace App\Apple\Service\Trait;

use App\Models\Account;
use Weijiajia\Response\Response;

trait HasSign
{
    /**
     * 签名并完成用户认证过程
     *
     * @param string $accountName 用户账号名
     * @param string $password 用户密码
     * @return Response 认证成功后的响应对象
     *
     * 此方法首先调用 `authenticate` 方法进行账号密码的验证，
     * 随后获取认证响应、存储电话号码数据至数据库、缓存可信电话号码、
     * 更新或创建用户账号信息，并最终建立登录会话。
     * @throws \JsonException
     */
    public function sign(string $accountName,string $password): Response
    {
        $this->authenticate($accountName, $password);

        $response = $this->fetchAuthResponse();

        $this->storePhoneData($response);

        $this->cacheTrustedPhoneNumber($response);

       $this->updateOrCreateAccount($accountName,$password);

        $this->establishLoginSession();

        return $response;
    }

    /**
     * 认证用户账户
     *
     * 根据提供的用户名和密码，尝试登录苹果客户端认证服务。
     *
     * @param string $accountName 用户名
     * @param string $password 密码
     *
     * @return void
     */
    private function authenticate(string $accountName, string $password): void
    {
        $this->appleClient->authLogin($accountName, $password);
    }

    /**
     * 获取认证响应
     *
     * @return Response 认证服务的响应对象
     */
    private function fetchAuthResponse(): Response
    {
        return $this->appleClient->auth();
    }

    /**
     * 更新或创建账户记录
     *
     * 根据给定的账号名和密码，检查数据库中是否存在该账号。如果存在，则更新其密码；
     * 如果不存在，则创建一个新的账户记录。操作完成后，会将该账户设置到当前上下文中。
     *
     * @param string $accountName 账户名
     * @param string $password 密码
     *
     * @return ?Account 成功时返回更新或创建的账户对象，失败时返回 null
     */
    private function updateOrCreateAccount(string $accountName, string $password): ?Account
    {
        $account = Account::updateOrCreate(
            ['account' => $accountName],
            ['password' => $password]
        );

        $this->setAccount($account);

        return $account;
    }
}
