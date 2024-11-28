<?php

namespace Modules\AppleClient\Service\Trait;

use Illuminate\Support\Facades\Cache;
use Modules\AppleClient\Service\DataConstruct\Icloud\Authenticate\Authenticate;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Auth\BasicAuthenticator;

trait HasAuthenticate
{
    protected static ?Authenticate $authenticate = null;

    public function resetAuthenticate(): static
    {
        self::$authenticate = null;

        return $this;
    }

    public function hasAuthenticate(): bool
    {
        return $this->getAuthenticate() !== null;
    }

    public function getAuthenticate(): ?Authenticate
    {
        return self::$authenticate ??= Cache::get($this->getAuthenticateCacheKey());
    }

    /**
     * 获取缓存键
     */
    protected function getAuthenticateCacheKey(): string
    {
        return "authenticate:{$this->getAccount()->getSessionId()}";
    }

    /**
     * 设置认证信息
     */
    public function setupAuthentication(Authenticate $authenticate): void
    {
        $this->getIcloudConnector()->authenticate(
            new BasicAuthenticator(
                $authenticate->appleAccountInfo->dsid,
                $authenticate->tokens->mmeAuthToken
            )
        );
    }

    public function fetchAuthenticateLogin(): Authenticate
    {
        $response = $this->getIcloudConnector()
            ->getAuthenticateResources()
            ->authenticate(
                appleId: $this->getAccount()->account,
                password: $this->getAccount()->password,
            );

        /**
         * @var Authenticate $authenticate
         */
        $authenticate = $response->dto();

        return $authenticate;
    }

    /**
     * @param string $code
     * @return Authenticate
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function fetchAuthenticateAuth(string $code): Authenticate
    {
        $response = $this->getIcloudConnector()
            ->getAuthenticateResources()
            ->authenticate(
                appleId: $this->getAccount()->account,
                password: $this->getAccount()->password,
                authCode: $code,
            );

        /**
         * @var Authenticate $authenticate
         */
        $authenticate = $response->dto();

        $this->withAuthenticate($authenticate);

        return $authenticate;
    }

    public function withAuthenticate(Authenticate $authenticate): static
    {
        self::$authenticate = $authenticate;
        Cache::set($this->getAuthenticateCacheKey(), $authenticate);

        return $this;
    }

}
