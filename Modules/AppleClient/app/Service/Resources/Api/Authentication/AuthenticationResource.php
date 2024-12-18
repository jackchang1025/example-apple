<?php

namespace Modules\AppleClient\Service\Resources\Api\Authentication;

use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\Authenticate\Authenticate;
use Modules\AppleClient\Service\Resources\Api\Icloud\IcloudResource;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Auth\BasicAuthenticator;
use Modules\AppleClient\Events\Authenticated\AuthenticatedEvent;

class AuthenticationResource
{
    protected ?Authenticate $authenticate = null;

    public function __construct(protected IcloudResource $resource)
    {

    }

    public function getResource(): IcloudResource
    {
        return $this->resource;
    }

    public function getAuthenticate(): ?Authenticate
    {
        return $this->authenticate;
    }

    public function setAuthenticate(Authenticate $authenticate): void
    {
        $this->authenticate = $authenticate;
    }

    /**
     * @return Authenticate
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function fetchAuthenticateLogin(): Authenticate
    {
        return $this->getResource()->getIcloudConnector()
            ->getAuthenticateResources()
            ->authenticate(
                appleId: $this->getResource()->getResource()->getApple()->getAccount()->account,
                password: $this->getResource()->getResource()->getApple()->getAccount()->password,
            );
    }

    /**
     * @param string $code
     * @return Authenticate
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function fetchAuthenticateAuth(string $code): Authenticate
    {
        $authenticate = $this->getResource()->getIcloudConnector()
            ->getAuthenticateResources()
            ->authenticate(
                appleId: $this->getResource()->getResource()->getApple()->getAccount()->account,
                password: $this->getResource()->getResource()->getApple()->getAccount()->password,
                authCode: $code,
            );

        $this->authenticate = $authenticate;

        $this->getResource()->getIcloudConnector()->authenticate(
            new BasicAuthenticator($authenticate->appleAccountInfo->dsid, $authenticate->tokens->mmeAuthToken)
        );

        //执行登录事件
        $this->getResource()->getResource()->getApple()->getDispatcher()?->dispatch(
            new AuthenticatedEvent($this->getResource()->getResource()->getApple())
        );

        return $authenticate;
    }
}
