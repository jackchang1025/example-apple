<?php

namespace Modules\AppleClient\Service\DataConstruct\Icloud\Authenticate;

use Modules\AppleClient\Service\DataConstruct\Data;

class Authenticate extends Data
{
    public function __construct(
        public int $protocolVersion,
        public ?AppleAccountInfo $appleAccountInfo = null,
        public ?Tokens $tokens = null,
        public ?string $title = null,
        public ?string $localizedError = null,
        public ?string $message = null
    ) {
    }

    public function isisVerification(): bool
    {
        return $this->title === 'Verification Required';
    }
}
