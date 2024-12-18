<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Resources;

use Modules\AppleClient\Service\Integrations\AppleId\Dto\Request\AddPayment\AddPayment;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Payment\PaymentConfig;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\Payment\AddPaymentRequest;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\Payment\PaymentRequest;
use Modules\AppleClient\Service\Integrations\BaseResource;
use Modules\AppleClient\Service\Response\Response;

class PaymentResources extends BaseResource
{
    public function addPayment(AddPayment $data): Response
    {
        return $this->connector->send(new AddPaymentRequest($data));
    }

    /**
     * @return PaymentConfig
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function payments(): PaymentConfig
    {
        return $this->connector->send(new PaymentRequest())->dto();
    }
}
