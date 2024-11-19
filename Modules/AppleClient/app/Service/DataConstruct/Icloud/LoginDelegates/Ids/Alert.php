<?php

namespace Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\Ids;

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
