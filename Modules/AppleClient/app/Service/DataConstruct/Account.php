<?php

namespace Modules\AppleClient\Service\DataConstruct;

class Account extends Data
{

    public function __construct(public string $appleId, public string $password)
    {
    }
}
