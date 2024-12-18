<?php

namespace Modules\AppleClient\Service\Resources;

use Modules\AppleClient\Service\Apple;

abstract class Resource
{
    public function __construct(
        protected Apple $apple,
    ) {
    }

    public function getApple(): Apple
    {
        return $this->apple;
    }

}
