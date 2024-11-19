<?php

namespace Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\MobileMe;


use Modules\AppleClient\Service\DataConstruct\Data;

class ServiceData extends Data
{
    public function __construct(
        public string $protocolVersion,
        public Token $tokens
    ) {

    }
}
