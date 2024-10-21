<?php

namespace Modules\AppleClient\Service\DataConstruct;

use Modules\AppleClient\Service\DataConstruct\Auth\Auth;
use Modules\AppleClient\Service\DataConstruct\VerificationCode\AuthenticationData;
use Modules\AppleClient\Service\Response\Response;

trait HasFromResponse
{
    /**
     * @param Response $response
     * @return HasFromResponse
     * @throws \JsonException
     */
    public static function fromResponse(Response $response): static
    {
        return self::from($response->json());
    }
}
