<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Request;

use Modules\AppleClient\Service\DataConstruct\Icloud\VerifyCVV\VerifyCVV;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\VerifyCVVRequestDto;
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
        public readonly VerifyCVVRequestDto $dto
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/setup/mac/family/verifyCVV';
    }

    public function defaultBody(): array
    {
        return $this->dto->toArray();
    }

    public function createDtoFromResponse(Response $response): VerifyCVV
    {
        return VerifyCVV::from($response->json());
    }
}
