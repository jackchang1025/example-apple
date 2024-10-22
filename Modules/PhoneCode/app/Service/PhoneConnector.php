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

    /**
     * @param string $url
     * @return \Saloon\Http\Response
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function getPhoneCode(string $url): \Saloon\Http\Response
    {
        return $this->send(new PhoneRequest($url));
    }

    /**
     * @param string $url
     * @param PhoneCodeParserInterface $phoneCodeParser
     * @param int $attempts
     * @return string
     * @throws AttemptBindPhoneCodeException
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function attemptGetPhoneCode(string $url, PhoneCodeParserInterface $phoneCodeParser, int $attempts = 5): string
    {
        for ($i = 0; $i < $attempts; $i++) {

            $response = $this->getPhoneCode($url);

            if ($code = $phoneCodeParser->parse($response->body())) {
                return $code;
            }

            usleep(5000);
        }

        throw new AttemptBindPhoneCodeException("Attempt {$attempts} times failed to get phone code");
    }
}
