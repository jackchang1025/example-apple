<?php

namespace Modules\PhoneCode\Service\Request;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class PhoneRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(protected string $uri){}

    public ?int $tries = 5;

    public ?int $retryInterval = 5000;

    public ?bool $useExponentialBackoff = true;

    public ?bool $throwOnMaxTries = false;

    protected function defaultDelay(): ?int
    {
        return 1000 * 10;
    }

    public function resolveEndpoint(): string
    {
        return $this->uri;
    }
}
