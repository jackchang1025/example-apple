<?php

namespace Modules\AppleClient\Service\DataConstruct;


use Saloon\Http\Response;

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

    public static function fromXml(Response $response): static
    {
        return self::from($response->xmlToCollection()->toArray());
    }
}
