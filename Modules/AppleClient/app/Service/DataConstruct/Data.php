<?php

namespace Modules\AppleClient\Service\DataConstruct;

use Spatie\LaravelData\Data as BaseData;

class Data extends BaseData
{
    use HasFromResponse;


    public function isSuccess(): bool
    {
        return isset($this->status) && $this->status === 0;
    }
}
