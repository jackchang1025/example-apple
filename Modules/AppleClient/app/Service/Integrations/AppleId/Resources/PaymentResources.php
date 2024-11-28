<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Resources;

use Modules\AppleClient\Service\Integrations\AppleId\Dto\AddPaymentDto;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\AddPaymentRequest;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\PaymentRequest;
use Modules\AppleClient\Service\Integrations\BaseResource;
use Modules\AppleClient\Service\Response\Response;

class PaymentResources extends BaseResource
{
    public function addPayment(AddPaymentDto $addPaymentDto): Response
    {
        return $this->connector->send(new AddPaymentRequest($addPaymentDto));
    }

    public function payments(): Response
    {
        return $this->connector->send(new PaymentRequest());
    }
}
