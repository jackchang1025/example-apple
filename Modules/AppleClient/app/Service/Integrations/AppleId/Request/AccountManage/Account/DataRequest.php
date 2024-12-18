<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\Account;

use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Payment\PaymentConfig;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class DataRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/account/manage/data';
    }

    public function createDtoFromResponse(Response $response): PaymentConfig
    {
        return PaymentConfig::from($response->json());
    }
}
