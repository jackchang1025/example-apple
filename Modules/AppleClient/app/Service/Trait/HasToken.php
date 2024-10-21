<?php

namespace Modules\AppleClient\Service\Trait;

use Modules\AppleClient\Service\DataConstruct\HasFromResponse;
use Modules\AppleClient\Service\DataConstruct\Token\Token;

trait HasToken
{

    protected ?Token $token = null;

    public function withToken(?Token $token = null): static
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return Token
     * @throws \JsonException
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function getToken(): Token
    {
        return $this->token ??= $this->token();
    }

    /**
     * @return Token
     * @throws \JsonException
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function refreshToken(): Token
    {
        return $this->token = $this->token();
    }

    /**
     * @return Token
     * @throws \JsonException
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    protected function token(): Token
    {
        return Token::fromResponse($this->getClient()->token());
    }
}
