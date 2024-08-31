<?php

namespace App\Apple\Integrations\Idmsa\Request;

use App\Apple\Trait\HasAppleConfig;
use Psr\Http\Message\RequestInterface;
use Saloon\Http\Request as SaloonRequest;

abstract class Request extends SaloonRequest
{
    use HasAppleConfig;

    protected ?string $uuid = null;

    protected function buildUUid(): string
    {
        return $this->uuid ??= sprintf('auth-%s', uniqid());
    }
}
