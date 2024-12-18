<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\Payment;

use Modules\AppleClient\Service\Integrations\AppleId\Dto\Request\AddPayment\AddPayment;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

class AddPaymentRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;


    public function __construct(
        public AddPayment $dto,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/account/manage/payment/method/card/{$this->dto->id}";
    }

    protected function defaultBody(): array
    {
        return $this->dto->toArray();
    }
}
