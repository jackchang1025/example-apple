<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Request;

use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyInfo\FamilyInfo;
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
        public readonly string $organizerAppleId,//主账号
        public readonly string $organizerAppleIdForPurchases,//付款账号
        public readonly string $organizerAppleIdForPurchasesPassword,//付款账号密码
        public readonly bool $organizerShareMyLocationEnabledDefault = true,
        public readonly int $iTunesTosVersion = 284005
    ) {
    }

    public function defaultHeaders(): array
    {
        return [
            'X-MMe-LoggedIn-AppleID' => $this->organizerAppleId,
        ];
    }

    public function resolveEndpoint(): string
    {
        return '/setup/mac/family/createFamily';
    }

    public function defaultBody(): array
    {
        return [
            "organizerAppleId"                       => $this->organizerAppleId,
            "organizerAppleIdForPurchases"           => $this->organizerAppleIdForPurchases,
            "organizerAppleIdForPurchasesPassword"   => $this->organizerAppleIdForPurchasesPassword,
            "organizerShareMyLocationEnabledDefault" => $this->organizerShareMyLocationEnabledDefault,
            "iTunesTosVersion"                       => $this->iTunesTosVersion,
        ];
    }

    public function createDtoFromResponse(Response $response): FamilyInfo
    {
        return FamilyInfo::from($response->json());
    }
}
