<?php

namespace Modules\AppleClient\Service\DataConstruct\Payment;

use Modules\AppleClient\Service\DataConstruct\Data;

class Option extends Data
{
    public function __construct(
        /** @var string 支付方式名称 */
        public string $name,

        /** @var string|null 支付方式显示名称 */
        public ?string $displayName = null
    ) {
    }

}
