<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Request;

use Modules\AppleClient\Service\DataConstruct\Icloud\Authenticate\Authenticate;
use Modules\AppleClient\Service\Exception\AccountException;
use Modules\AppleClient\Service\Exception\UnauthorizedException;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Enums\Method;
use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\Http\Response;
use Throwable;

class AuthenticateRequest extends Request
{
    protected Method $method = Method::POST;

    public function __construct(public string $appleId, public string $password, public ?string $code = null)
    {
    }

    protected function defaultAuth(): BasicAuthenticator
    {
        return new BasicAuthenticator($this->appleId, $this->password.$this->code);
    }

    public function resolveEndpoint(): string
    {
        return "/setup/authenticate/{$this->appleId}";
    }

    public function hasRequestFailed(Response $response): ?bool
    {
        if ($response->clientError() && $response->status() === 409) {
            return false;
        }

        return null;
    }

    public function getRequestException(Response $response, ?Throwable $senderException): ?Throwable
    {
        if ($response->status() === 401) {

            if ($this->code) {
                return new UnauthorizedException("Invalid verification code");
            }

            return new AccountException("Invalid account or password");
        }

        return null;
    }

    public function createDtoFromResponse(Response $response): Authenticate
    {
        /**
         * @var \Modules\AppleClient\Service\Response\Response $response ;
         */
        return Authenticate::fromXml($response);
    }
}
