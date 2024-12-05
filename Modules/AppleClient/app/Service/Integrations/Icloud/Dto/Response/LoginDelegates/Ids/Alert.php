<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\LoginDelegates\Ids;
use Modules\AppleClient\Service\DataConstruct\Data;

class Alert extends Data
{

    public function __construct(
        string $title,
        string $body,
        string $button
    ) {
    }
}
