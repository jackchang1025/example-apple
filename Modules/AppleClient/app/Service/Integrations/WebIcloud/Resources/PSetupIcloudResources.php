<?php

namespace Modules\AppleClient\Service\Integrations\WebIcloud\Resources;

use Modules\AppleClient\Service\Integrations\BaseResource;
use Modules\AppleClient\Service\Integrations\WebIcloud\Dto\Request\AccountLogin\AccountLogin;
use Modules\AppleClient\Service\Integrations\WebIcloud\Dto\Response\AccountLogin\AccountLogin as AccountLoginResponse;
use Modules\AppleClient\Service\Integrations\WebIcloud\Dto\Response\Devices\Device;
use Modules\AppleClient\Service\Integrations\WebIcloud\Request\AccountLoginRequest;
use Modules\AppleClient\Service\Integrations\WebIcloud\Request\GetDevicesRequest;

class PSetupIcloudResources extends BaseResource
{
    public function accountLogin(AccountLogin $data): AccountLoginResponse
    {
        return $this->getConnector()->send(new AccountLoginRequest($data))->dto();
    }

    public function getDevices(): Device
    {
        return $this->getConnector()->send(new GetDevicesRequest())->dto();
    }
}
