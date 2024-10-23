<?php

namespace Modules\PhoneCode\Service;

use Modules\PhoneCode\Service\Exception\AttemptBindPhoneCodeException;
use Modules\PhoneCode\Service\Request\PhoneRequest;
use Psr\Log\LoggerInterface;

class PhoneCodeService
{
    public function __construct(protected LoggerInterface $logger, protected PhoneConnector $connector)
    {
        $this->connector->withLogger($this->logger);
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
    public function attemptGetPhoneCode(
        string $url,
        PhoneCodeParserInterface $phoneCodeParser,
        int $attempts = 5
    ): string {
        for ($i = 0; $i < $attempts; $i++) {

            $response = $this->getPhoneCode($url);

            if ($code = $phoneCodeParser->parse($response->body())) {
                return $code;
            }

            usleep(5000);
        }

        throw new AttemptBindPhoneCodeException("Attempt {$attempts} times failed to get phone code");
    }

    /**
     * @param string $url
     * @return \Saloon\Http\Response
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function getPhoneCode(string $url): \Saloon\Http\Response
    {
        return $this->connector->send(new PhoneRequest($url));
    }
}
