<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth;

use Modules\AppleClient\Service\Integrations\Idmsa\Dto\Auth\AuthData;
use Modules\AppleClient\Service\Integrations\Request;
use Modules\AppleClient\Service\Response\Response;
use Saloon\Enums\Method;

class Auth extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/appleauth/auth';
    }

    public function createDtoFromResponse(Response $response): AuthData
    {
        return AuthData::from($response->json());
    }

    public function defaultHeaders(): array
    {
        return [
            'Accept' => 'text/html',
            'Content-Type' => 'application/json',
        ];
    }
}
