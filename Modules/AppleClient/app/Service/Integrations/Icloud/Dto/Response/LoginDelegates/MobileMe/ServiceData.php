<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\LoginDelegates\MobileMe;


use Modules\AppleClient\Service\DataConstruct\Data;

class ServiceData extends Data
{
    public function __construct(
        public string $protocolVersion,
        public Token $tokens
    ) {

    }
}
