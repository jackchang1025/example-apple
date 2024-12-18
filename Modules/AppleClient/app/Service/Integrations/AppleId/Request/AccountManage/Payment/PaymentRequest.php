<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\Payment;

use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Payment\PaymentConfig;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class PaymentRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/account/manage/payment';
    }

    public function createDtoFromResponse(Response $response): PaymentConfig
    {
        return PaymentConfig::from($response->json());
    }
}
