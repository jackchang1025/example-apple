<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage;

use Modules\AppleClient\Service\DataConstruct\Payment\PaymentConfig;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class AccountManage extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/account/manage';
    }

    public function createDtoFromResponse(Response $response): PaymentConfig
    {
        return PaymentConfig::from($response->json());
    }
}
