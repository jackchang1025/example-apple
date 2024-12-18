<?php

namespace Modules\AppleClient\Service\Resources\Web\Icloud;

use Modules\AppleClient\Service\Cookies\CookieAuthenticator;
use Modules\AppleClient\Service\Header\HeaderSynchronizeInterface;
use Modules\AppleClient\Service\Integrations\WebIcloud\WebIcloudConnector;
use Modules\AppleClient\Service\Integrations\Idmsa\IdmsaConnector;
use Modules\AppleClient\Service\Integrations\WebIcloud\Dto\Response\Devices\Devices;
use Modules\AppleClient\Service\Resources\Web\Idmsa\IdmsaResource;

class IcloudResource extends IdmsaResource
{
    protected ?WebIcloudConnector $webIcloudConnector = null;

    protected ?AuthenticateResources $authenticateResources = null;

    public function getAuthenticateResources(): AuthenticateResources
    {
        return $this->authenticateResources ??= new AuthenticateResources($this);
    }

    public function getAuthenticator(): CookieAuthenticator
    {
        return $this->authenticator ??= new CookieAuthenticator(
            $this->cookieJarFactory->create('icloud', $this->getWebResource()->getApple()->getAccount()->getSessionId())
        );
    }

    public function getHeaderSynchronize(): HeaderSynchronizeInterface
    {
        return $this->headerSynchronize ??= $this->headerSynchronizeFactory->create(
            'icloud',
            $this->getWebResource()->getApple()->getAccount()->getSessionId()
        );
    }

    public function getWebIcloudConnector(): WebIcloudConnector
    {
        return $this->webIcloudConnector ??= new WebIcloudConnector(
            apple: $this->getWebResource()->getApple(),
            authenticator: $this->getAuthenticator(),
            headerSynchronize: $this->getHeaderSynchronize()
        );
    }

    public function getIdmsaConnector(): IdmsaConnector
    {
        return $this->idmsaConnector ??= new IdmsaConnector(
            apple: $this->getWebResource()->getApple(),
            authenticator: $this->getAuthenticator(),
            headerSynchronize: $this->getHeaderSynchronize(),
            serviceKey: 'd39ba9916b7251055b22c7f910e2ea796ee65e98b2ddecea8f5dde8d9d1a815d',
            redirectUri: 'https://www.icloud.com'
        );
    }

    public function getDevices(): Devices
    {
        return $this->getWebIcloudConnector()->getAuthenticateResources()->getDevices();
    }

}
