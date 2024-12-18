<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\ValidatePassword;

use Illuminate\Support\Carbon;
use Modules\AppleClient\Service\DataConstruct\Data;

class ValidatePassword extends Data
{

    public function __construct(public bool $hasValidatePassword = false, public ?Carbon $updateAt = null)
    {
        if ($this->updateAt === null) {
            $this->updateAt = now();
        }
    }
}
