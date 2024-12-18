<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Payment;


use Modules\AppleClient\Service\DataConstruct\Data;
use Spatie\LaravelData\Attributes\MapInputName;

class PaymentMethodOption extends Data
{
    public function __construct(
        #[MapInputName('option')]
        public Option $option
    ) {
    }
}
