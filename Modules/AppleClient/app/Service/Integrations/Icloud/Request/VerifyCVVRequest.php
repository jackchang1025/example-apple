<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Request;

use Modules\AppleClient\Service\DataConstruct\Icloud\VerifyCVV\VerifyCVV;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class VerifyCVVRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly string $creditCardLastFourDigits,
        public readonly string $securityCode,
        public readonly string $creditCardId = 'MAST',
        public readonly string $verificationType = 'CVV',
        public readonly string $billingType = 'Card'
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/setup/mac/family/verifyCVV';
    }

    public function defaultBody(): array
    {
        return [
            "creditCardId"             => $this->creditCardId,
            "creditCardLastFourDigits" => $this->creditCardLastFourDigits,
            "securityCode"             => $this->securityCode,
            "verificationType"         => $this->verificationType,
            "billingType"              => $this->billingType,
        ];
    }

    public function createDtoFromResponse(Response $response): VerifyCVV
    {
        return VerifyCVV::fromResponse($response);
    }
}
