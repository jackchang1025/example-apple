<?php

namespace Modules\AppleClient\Service\Trait;

use Illuminate\Support\Facades\Cache;
use Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\LoginDelegates;
use Modules\AppleClient\Service\Exception\AppleRequestException\LoginRequestException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Response\Response;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Auth\BasicAuthenticator;

trait HasLoginDelegates
{
    private const string DEFAULT_CLIENT_ID = '67BDADCA-6E66-7ED7-A01A-5EB3C5D95CE3';
    private const string DEFAULT_PROTOCOL_VERSION = '4';
    private const int CACHE_TTL = 60 * 60; // 60 minutes in seconds


    protected static ?loginDelegates $loginDelegates = null;

    /**
     * 初始化登录流程
     *
     * @throws LoginRequestException
     * @throws RequestException
     * @throws FatalRequestException
     */
    public function initializeLogin(
        string $clientId = self::DEFAULT_CLIENT_ID,
        string $protocolVersion = self::DEFAULT_PROTOCOL_VERSION
    ): Response {
        return $this->getIcloudConnector()
            ->getResources()
            ->loginDelegatesRequest(
                appleId: $this->getAccount()->account,
                password: $this->getAccount()->password,
                authCode: '',
                clientId: $clientId,
                protocolVersion: $protocolVersion
            );
    }

    /**
     * 使用验证码完成认证
     * @param string $authCode
     * @param string $clientId
     * @param string $protocolVersion
     * @return LoginDelegates
     * @throws FatalRequestException
     * @throws LoginRequestException
     * @throws RequestException
     * @throws VerificationCodeException
     */
    public function completeAuthentication(
        string $authCode,
        string $clientId = self::DEFAULT_CLIENT_ID,
        string $protocolVersion = self::DEFAULT_PROTOCOL_VERSION
    ): LoginDelegates {

        $loginDelegates = $this->performAuthDelegatesRequest($authCode, $clientId, $protocolVersion);


        $this->setupAuthentication($loginDelegates);
        $this->cacheLoginDelegates($loginDelegates);

        return $loginDelegates;
    }

    /**
     * 执行认证请求
     * * @throws RequestException|LoginRequestException|VerificationCodeException|FatalRequestException
     */
    private function performAuthDelegatesRequest(
        string $authCode,
        string $clientId,
        string $protocolVersion
    ): LoginDelegates {
        return $this->getIcloudConnector()
            ->getResources()
            ->loginDelegatesRequest(
                $this->getAccount()->account,
                $this->getAccount()->password,
                $authCode,
                $clientId,
                $protocolVersion
            )->dto();
    }

    /**
     * 设置认证信息
     */
    private function setupAuthentication(LoginDelegates $loginDelegates): void
    {
        $this->getIcloudConnector()->authenticate(
            new BasicAuthenticator(
                $loginDelegates->dsid,
                $loginDelegates->delegates->mobileMeService->serviceData->tokens->mmeAuthToken
            )
        );
    }

    /**
     * 缓存登录委托信息
     */
    private function cacheLoginDelegates(LoginDelegates $loginDelegates): void
    {
        $cacheKey = $this->getLoginDelegatesCacheKey();
        Cache::put($cacheKey, $loginDelegates, self::CACHE_TTL);
        self::$loginDelegates = $loginDelegates;

        $this->setupAuthentication($loginDelegates);
    }

    /**
     * 获取缓存键
     */
    private function getLoginDelegatesCacheKey(): string
    {
        return "login_delegates:{$this->getAccount()->getSessionId()}";
    }

    /**
     * 刷新登录状态
     */
    public function refreshLoginState(): bool
    {
        if (!$this->isLoginValid()) {
            return false;
        }

        $this->cacheLoginDelegates($this->getLoginDelegates());

        return true;
    }

    /**
     * 检查登录状态是否有效
     */
    public function isLoginValid(): bool
    {
        return $this->getLoginDelegates() !== null;
    }

    public function getLoginDelegates(): ?loginDelegates
    {
        return self::$loginDelegates ??= Cache::get($this->getLoginDelegatesCacheKey());
    }

    /**
     * 清除登录状态
     */
    private function clearLoginState(): void
    {
        self::$loginDelegates = null;
        Cache::forget($this->getLoginDelegatesCacheKey());
    }
}
