<?php

namespace App\Apple\Integrations\Idmsa\Request\Appleauth;

use App\Apple\Exception\VerificationCodeException;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use Throwable;

class VerifyTrustedDeviceSecurityCode extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(protected string $code)
    {
    }

    public function resolveEndpoint(): string
    {
        return '/appleauth/auth/verify/trusteddevice/securitycode';
    }

    public function defaultBody(): array
    {
        return [
            'securityCode' => [
                'code' => $this->code,
            ],
        ];
    }

    /**
     * @param Response $response
     * @param Throwable|null $senderException
     * @return Throwable|null
     * @throws \JsonException
     */
    public function getRequestException(Response $response, ?Throwable $senderException): ?Throwable
    {
        return new VerificationCodeException($response->service_errors_first()?->getMessage(), $response->status());
    }

    public function hasRequestFailed(Response $response):bool
    {
        return $response->status() !== 412 && ($response->clientError() || $response->serverError());
    }

}
