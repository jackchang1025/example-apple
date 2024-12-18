<?php

namespace Modules\AppleClient\Service\Resources\Web\AppleId;

use Modules\AppleClient\Service\Cookies\CookieAuthenticator;
use Modules\AppleClient\Service\Header\HeaderSynchronizeInterface;
use Modules\AppleClient\Service\Integrations\AppleId\AppleIdConnector;
use Modules\AppleClient\Service\Integrations\Buy\BuyConnector;
use Modules\AppleClient\Service\Integrations\Idmsa\IdmsaConnector;
use Modules\AppleClient\Service\Resources\Web\Idmsa\IdmsaResource;

class AppleIdResource extends IdmsaResource
{
    protected ?AppleIdConnector $appleIdConnector = null;
    protected ?DevicesResource $devicesResource = null;
    protected ?PaymentResource $paymentResource = null;
    protected ?SecurityPhoneResource $securityPhoneResource = null;
    protected ?AccountManagerResource $accountManagerResource = null;

    public function getAccountManagerResource(): AccountManagerResource
    {
        return $this->accountManagerResource ??= new AccountManagerResource($this);
    }

    public function getAuthenticator(): CookieAuthenticator
    {
        return $this->authenticator ??= new CookieAuthenticator(
            $this->cookieJarFactory->create(
                'apple_id',
                $this->getWebResource()->getApple()->getAccount()->getSessionId()
            )
        );
    }

    public function getHeaderSynchronize(): HeaderSynchronizeInterface
    {
        return $this->headerSynchronize ??= $this->headerSynchronizeFactory->create(
            'apple_id',
            $this->getWebResource()->getApple()->getAccount()->getSessionId()
        );
    }

    public function getIdmsaConnector(): IdmsaConnector
    {
        return $this->idmsaConnector ??= new IdmsaConnector(
            apple: $this->getWebResource()->getApple(),
            authenticator: $this->getAuthenticator(),
            headerSynchronize: $this->getHeaderSynchronize(),
            serviceKey: $this->getWebResource()->getApple()->getConfig()->getServiceKey(),
            redirectUri: $this->getWebResource()->getApple()->getConfig()->getApiUrl()
        );
    }

    public function getAppleIdConnector(): AppleIdConnector
    {
        return $this->appleIdConnector ??= new AppleIdConnector(
            apple: $this->getWebResource()->getApple(),
            authenticator: $this->getAuthenticator(),
            headerSynchronize: $this->getHeaderSynchronize()
        );
    }

    public function getSecurityPhoneResource(): SecurityPhoneResource
    {
        return $this->securityPhoneResource ??= new SecurityPhoneResource($this);
    }

    public function getDevicesResource(): DevicesResource
    {
        return $this->devicesResource ??= new DevicesResource($this);
    }

    public function getPaymentResource(): PaymentResource
    {
        return $this->paymentResource ??= new PaymentResource($this);
    }

    public function getBuyConnector(): BuyConnector
    {
        return $this->buyConnector ??= new BuyConnector($this->getWebResource()->getApple());
    }
}



