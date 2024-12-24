<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto\Request\Payment;

use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Request\AddPayment\BillingAddress;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Request\AddPayment\NameOnCard;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Request\AddPayment\PhoneNumber;

abstract class AddPayment extends Data
{
    public function __construct(
        public string $partnerToken,
        public NameOnCard $nameOnCard,
        public PhoneNumber $phoneNumber,
        public BillingAddress $billingAddress,
        public int $id,
    ) {
    }
}
