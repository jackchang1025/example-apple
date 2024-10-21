<?php

namespace Modules\AppleClient\Service\DataConstruct\Sign;

use Illuminate\Support\Carbon;
use Modules\AppleClient\Service\Response\Response;
use Modules\AppleClient\Service\DataConstruct\Data;

class Sign extends Data
{
    public function __construct(
        public string $authType,
        public ?Carbon $expiresAt = null
    ) {
        if ($this->expiresAt === null) {
            $this->expiresAt = now()->addSeconds(30);
        }
    }

    public static function fromResponse(Response $response): static
    {
        return new self(
            authType: $response->json('authType'),
            expiresAt: now()->addSeconds(30)
        );
    }

    public function isValid(): bool
    {
        return $this->expiresAt?->isFuture();
    }
}
