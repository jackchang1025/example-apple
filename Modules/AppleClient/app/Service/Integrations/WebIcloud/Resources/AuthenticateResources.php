<?php

namespace Modules\AppleClient\Service\Integrations\WebIcloud\Resources;

use Modules\AppleClient\Service\Integrations\BaseResource;
use Modules\AppleClient\Service\Integrations\WebIcloud\Dto\Request\AccountLogin\AccountLogin;
use Modules\AppleClient\Service\Integrations\WebIcloud\Dto\Response\AccountLogin\AccountLogin as AccountLoginResponse;
use Modules\AppleClient\Service\Integrations\WebIcloud\Dto\Response\Devices\Devices;
use Modules\AppleClient\Service\Integrations\WebIcloud\Request\AccountLoginRequest;
use Modules\AppleClient\Service\Integrations\WebIcloud\Request\GetDevicesRequest;

class AuthenticateResources extends BaseResource
{
    public function accountLogin(AccountLogin $data): AccountLoginResponse
    {
        return $this->getConnector()->send(new AccountLoginRequest($data))->dto();
    }

    public function getDevices(): Devices
    {
        return $this->getConnector()->send(new GetDevicesRequest())->dto();
    }
}
