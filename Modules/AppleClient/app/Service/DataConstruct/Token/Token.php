<?php

namespace Modules\AppleClient\Service\DataConstruct\Token;

use Illuminate\Support\Carbon;
use Modules\AppleClient\Service\DataConstruct\Data;

class Token extends Data
{

    public function __construct(public bool $hasToken = false, public ?Carbon $updateAt = null)
    {
        if ($this->updateAt === null) {
            $this->updateAt = now();
        }
    }
}
