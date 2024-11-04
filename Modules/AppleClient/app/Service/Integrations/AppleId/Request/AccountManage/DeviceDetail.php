<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage;

use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Enums\Method;

class DeviceDetail extends Request
{

    protected Method $method = Method::GET;

    /**
     * @param string $paymentId
     */
    public function __construct(public string $paymentId)
    {
    }

    public function resolveEndpoint(): string
    {
        return "/account/manage/security/devices/{$this->paymentId}";
    }
}
