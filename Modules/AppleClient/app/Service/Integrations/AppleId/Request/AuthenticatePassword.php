<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Integrations\AppleId\Request;

use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class AuthenticatePassword extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(protected string $password)
    {
    }

    public function resolveEndpoint(): string
    {
        return '/authenticate/password';
    }

    public function hasRequestFailed(Response $response): bool
    {
        if ($response->clientError() && $response->status() === 409) {
            return false;
        }

        return $response->serverError() || $response->clientError();
    }

    protected function defaultBody(): array
    {
        return [
            'password' => $this->password,
        ];
    }
}
