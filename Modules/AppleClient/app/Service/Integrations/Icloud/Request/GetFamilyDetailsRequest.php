<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Request;

use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\FamilyDetails\FamilyDetails;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class GetFamilyDetailsRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/setup/family/getFamilyDetails';
    }

    public function defaultHeaders(): array
    {
        return [
            "accept-language"   => "zh-cn",
            "user-agent"        => "Accounts/113 CFNetwork/711.2.23 Darwin/14.0.0",
            "accept"            => "*/*",
            "connection"        => "keep-alive",
            "x-mme-client-info" => "<iPhone7,1> <iPhone OS;8.1;12B411> <com.apple.AppleAccount/1.0 (com.apple.Accounts/113)>",
            "proxy-connection"  => "keep-alive",
            "x-mme-country"     => "CN",
            "Accept-Encoding"   => "gzip, deflate",
            "Host"              => "setup.icloud.com",
        ];
    }

    /**
     * @param Response $response
     * @return FamilyDetails
     */
    public function createDtoFromResponse(Response $response): FamilyDetails
    {
        /**
         * @var \Modules\AppleClient\Service\Response\Response $response ;
         */
        return FamilyDetails::fromXml($response);
    }
}
