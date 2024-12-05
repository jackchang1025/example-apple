<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Resources;

use Modules\AppleClient\Service\Integrations\BaseResource;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Request\AccountLogin\AccountLogin;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\AccountLogin\AccountLogin as AccountLoginResponse;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\Devices\Device;
use Modules\AppleClient\Service\Integrations\Icloud\Request\AccountLoginRequest;
use Modules\AppleClient\Service\Integrations\Icloud\Request\GetDevicesRequest;

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
