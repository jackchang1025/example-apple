<?php

namespace Modules\AppleClient\Service\Trait;

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
}
