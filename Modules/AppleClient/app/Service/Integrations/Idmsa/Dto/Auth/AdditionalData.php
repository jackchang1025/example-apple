<?php

namespace Modules\AppleClient\Service\Integrations\Idmsa\Dto\Auth;

use Modules\AppleClient\Service\DataConstruct\Data;

class AdditionalData extends Data
{
    /**
     * @param bool $canRoute2sv 是否可以路由到双重验证
     */
    public function __construct(
        public bool $canRoute2sv
    ) {
    }
}
