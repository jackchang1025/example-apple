<?php

namespace App\Apple\Integrations\Phone;

use App\Apple\Help\PhoneCodeParser;
use App\Apple\Service\Exception\AttemptBindPhoneCodeException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Throwable;

class PhoneCodeRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(protected string $uri)
    {
    }

    public ?int $tries = 5;

    public ?int $retryInterval = 5000;

    public ?bool $useExponentialBackoff = true;

    public ?bool $throwOnMaxTries = false;


    public function hasRequestFailed(Response $response): ?bool
    {
        return PhoneCodeParser::parse($response->body()) !== null;
    }

    public function getRequestException(Response $response, ?Throwable $senderException): ?Throwable
    {
        return new AttemptBindPhoneCodeException($response->body());
    }

    protected function defaultDelay(): ?int
    {
        return 1000 * 10;
    }

    public function resolveEndpoint(): string
    {
        return $this->uri;
    }

}
