<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Request;

use Modules\AppleClient\Service\Integrations\Icloud\Dto\Request\CreateFamily\CreateFamily;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\FamilyInfo\FamilyInfo;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class CreateFamilyRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly CreateFamily $data
    ) {
    }

    public function defaultHeaders(): array
    {
        return [
            'X-MMe-LoggedIn-AppleID' => $this->data->organizerAppleId,
        ];
    }

    public function resolveEndpoint(): string
    {
        return '/setup/mac/family/createFamily';
    }

    public function defaultBody(): array
    {
        return $this->data->toArray();
    }

    public function createDtoFromResponse(Response $response): FamilyInfo
    {
        return FamilyInfo::from($response->json());
    }
}
