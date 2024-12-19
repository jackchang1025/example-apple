<?php

namespace Modules\AppleClient\Service\Resources\Web\AppleId;

use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Payment\PaymentConfig;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class PaymentResource
{
    public function __construct(protected AppleIdResource $appleIdResource)
    {

    }

    public function getAppleIdResource(): AppleIdResource
    {
        return $this->appleIdResource;
    }

    /**
     * @return PaymentConfig
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function getPayment(): PaymentConfig
    {
        return $this->getAppleIdResource()->getAppleIdConnector()->getPaymentResources()->payments();
    }

    public function getPaymentInfos(): \Modules\AppleClient\Service\Response\Response
    {
        return $this->getAppleIdResource()->getBuyConnector()->getResources()->paymentInfosRequest();
    }
}