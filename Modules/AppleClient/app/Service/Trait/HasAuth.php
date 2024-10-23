<?php

namespace Modules\AppleClient\Service\Trait;

use Illuminate\Support\Facades\Cache;
use Modules\AppleClient\Service\DataConstruct\Auth\Auth;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

trait HasAuth
{
    protected ?Auth $auth = null;

    public function withAuth(?Auth $authData): static
    {
        $this->auth = $authData;

        return $this;
    }

    /**
     * @return Auth
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function auth(): Auth
    {
        return $this->auth ??= Cache::remember(
            "{$this->getAccount()->getSessionId()}:auth",
            60 * 5,
            fn() => Auth::fromResponse($this->getClient()->auth())
        );
    }

    /**
     * @return Auth
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function refreshAuth(): Auth
    {
        return $this->auth = Auth::fromResponse($this->getClient()->auth());
    }
}
