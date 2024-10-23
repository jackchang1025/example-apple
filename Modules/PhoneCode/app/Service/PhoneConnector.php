<?php

namespace Modules\PhoneCode\Service;

use Modules\AppleClient\Service\Trait\HasLogger;
use Modules\PhoneCode\Service\Exception\AttemptBindPhoneCodeException;
use Modules\PhoneCode\Service\Request\PhoneRequest;
use Psr\Log\LoggerInterface;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Connector;
use Saloon\Http\Request;

class PhoneConnector extends Connector
{
    use HasLogger;

    public function __construct(protected ?LoggerInterface $logger = null)
    {
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

    public function handleRetry(FatalRequestException|RequestException $exception, Request $request): bool
    {
        $response = $exception->getResponse();

        $this->formatResponseLog($response);

        return true;
    }
}
