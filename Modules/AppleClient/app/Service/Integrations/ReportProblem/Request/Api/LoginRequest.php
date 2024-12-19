<?php

namespace Modules\AppleClient\Service\Integrations\ReportProblem\Request\Api;

use Modules\AppleClient\Service\Integrations\ReportProblem\Data\Response\Login;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class LoginRequest extends Request
{
    protected Method $method = Method::GET;

    public function createDtoFromResponse(Response $response): Login
    {
        return Login::from($response->json());
    }

    public function resolveEndpoint(): string
    {
        return '/api/login';
    }
}
