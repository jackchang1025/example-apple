<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Request;

use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyInfo\FamilyInfo;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\AddFamilyMemberData;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class AddFamilyMemberRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly AddFamilyMemberData $data
    ) {

    }

    public function resolveEndpoint(): string
    {
        return '/setup/mac/family/addFamilyMember';
    }

    public function createDtoFromResponse(Response $response): FamilyInfo
    {
        return FamilyInfo::from($response->json());
    }

    public function defaultBody(): array
    {
        return $this->data->toArray();
    }
}
