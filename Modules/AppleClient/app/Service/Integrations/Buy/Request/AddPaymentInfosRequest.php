<?php

namespace Modules\AppleClient\Service\Integrations\Buy\Request;

use Modules\AppleClient\Service\Integrations\Buy\DTO\AddPaymentInfos;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

class AddPaymentInfosRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;


    public function __construct(
        public AddPaymentInfos $addPaymentInfosDto
    ) {
    }


    public function resolveEndpoint(): string
    {
        return '/account/v1/stackable/paymentInfos/add';
    }

    public function defaultBody(): array
    {
        return $this->addPaymentInfosDto->toArray();
    }
}
