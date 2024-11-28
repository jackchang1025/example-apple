<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Request;

use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyInfo\FamilyInfo;
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
        public readonly string $appleId,
        public readonly string $password,
        public readonly string $appleIdForPurchases,
        public readonly string $verificationToken,
        public readonly string $preferredAppleId,
        public readonly bool $shareMyLocationEnabledDefault = true,
        public readonly bool $shareMyPurchasesEnabledDefault = true,
    ) {

    }

    public function resolveEndpoint(): string
    {
        return '/setup/mac/family/addFamilyMember';
    }

    public function defaultBody(): array
    {
        return [
            "appleId"                        => $this->appleId,
            "password"                       => $this->password,
            "appleIdForPurchases"            => $this->appleIdForPurchases,
            "shareMyLocationEnabledDefault"  => $this->shareMyLocationEnabledDefault,
            "shareMyPurchasesEnabledDefault" => $this->shareMyPurchasesEnabledDefault,
            "verificationToken"              => $this->verificationToken,
            "preferredAppleId"               => $this->preferredAppleId,
        ];
    }

    public function createDtoFromResponse(Response $response): FamilyInfo
    {
        return FamilyInfo::from($response->json());
    }
}
