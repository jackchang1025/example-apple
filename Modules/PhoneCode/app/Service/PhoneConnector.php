<?php

namespace Modules\PhoneCode\Service;

use Modules\PhoneCode\Service\Exception\AttemptBindPhoneCodeException;
use Modules\PhoneCode\Service\Request\PhoneRequest;
use Psr\Log\LoggerInterface;
use Saloon\Http\Connector;

class PhoneConnector extends Connector
{
    use Logger;

    public function __construct(protected ?LoggerInterface $logger = null)
    {
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function resolveBaseUrl(): string
    {
        return '';
    }

    public function resolveResponseClass(): string
    {
        return Response::class;
    }

    public function attemptGetPhoneCode(string $url, PhoneCodeParserInterface $phoneCodeParser, int $attempts = 5): string
    {
        for ($i = 0; $i < $attempts; $i++) {

            $response = $this->send(new PhoneRequest($url));

            if ($code = $phoneCodeParser->parse($response->body())) {
                return $code;
            }
        }

        throw new AttemptBindPhoneCodeException("Attempt {$attempts} times failed to get phone code");
    }
}
