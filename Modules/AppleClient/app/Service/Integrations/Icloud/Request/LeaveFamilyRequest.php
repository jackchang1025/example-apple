<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Request;

use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\leaveFamily\leaveFamily;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class LeaveFamilyRequest extends Request
{
    protected Method $method = Method::POST;

    public function resolveEndpoint(): string
    {
        return '/setup/mac/family/leaveFamily';
    }

    public function createDtoFromResponse(Response $response): leaveFamily
    {
        return leaveFamily::from($response->json());
    }
}
