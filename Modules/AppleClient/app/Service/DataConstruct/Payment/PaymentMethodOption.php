<?php

namespace Modules\AppleClient\Service\DataConstruct\Payment;


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
