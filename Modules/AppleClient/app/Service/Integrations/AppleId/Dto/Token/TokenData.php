<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto\Token;

use Illuminate\Support\Carbon;
use Modules\AppleClient\Service\DataConstruct\Data;

class TokenData extends Data
{

    public function __construct(public bool $hasToken = false, public ?Carbon $updateAt = null)
    {
        if ($this->updateAt === null) {
            $this->updateAt = now();
        }
    }
}
