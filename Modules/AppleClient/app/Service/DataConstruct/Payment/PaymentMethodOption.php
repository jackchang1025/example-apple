<?php

namespace Modules\AppleClient\Service\DataConstruct\Payment;


use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class PaymentMethodOption extends Data
{
    public function __construct(
        #[MapInputName('option')]
        public Option $option
    ) {
    }
}
