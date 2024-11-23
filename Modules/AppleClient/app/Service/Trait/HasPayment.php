<?php

namespace Modules\AppleClient\Service\Trait;

use App\Models\Payment;
use Modules\AppleClient\Service\DataConstruct\Payment\PaymentConfig;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

trait HasPayment
{
    protected ?PaymentConfig $payment = null;

    /**
     * @return PaymentConfig
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function getPayment(): PaymentConfig
    {
        return $this->payment ??= $this->payment();
    }

    /**
     * @return Payment
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function fetchPaymentConfig(): Payment
    {
        return $this->getPayment()->primaryPaymentMethod->updateOrCreate($this->getAccount()->id);
    }

    /**
     * @return PaymentConfig
     * @throws \JsonException
     * @throws FatalRequestException
     * @throws RequestException
     */
    protected function payment(): PaymentConfig
    {
        return PaymentConfig::from($this->client->payment()->json());
    }

    /**
     * @return PaymentConfig
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */

    public function refreshPayment(): PaymentConfig
    {
        return $this->payment = $this->payment();
    }

    public function paymentInfos()
    {
        return $this->getClient()->getBuyConnector()->getResources()->paymentInfosRequest();
    }
}
