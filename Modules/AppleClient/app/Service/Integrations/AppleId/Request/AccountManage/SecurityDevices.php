<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage;

use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Enums\Method;

class SecurityDevices extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/account/manage/security/devices';
    }
}
