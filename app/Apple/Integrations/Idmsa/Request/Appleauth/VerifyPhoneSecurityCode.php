<?php

namespace App\Apple\Integrations\Idmsa\Request\Appleauth;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class VerifyPhoneSecurityCode extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $id,
        protected string $code
    ){}

    public function resolveEndpoint(): string
    {
        return '/appleauth/auth/verify/phone/securitycode';
    }

    public function defaultBody(): array
    {
        return[
            'phoneNumber'  => [
                'id' => $this->id,
            ],
            'securityCode' => [
                'code' => $this->code,
            ],
            'mode'         => 'sms',
        ];
    }

    public function hasRequestFailed(Response $response):bool
    {
        return $response->status() !== 412 && ($response->clientError() || $response->serverError());
    }
}
