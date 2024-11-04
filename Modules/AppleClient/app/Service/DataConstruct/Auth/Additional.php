<?php

namespace Modules\AppleClient\Service\DataConstruct\Auth;

use Modules\AppleClient\Service\DataConstruct\Data;

class Additional extends Data
{
    /**
     * @param bool $canRoute2sv 是否可以路由到双重验证
     */
    public function __construct(
        public bool $canRoute2sv
    ) {
    }
}
