<?php

namespace App\Apple;

use App\Apple\Help\PhoneCodeParser;
use App\Apple\Integrations\Phone\PhoneCodeRequest;
use App\Apple\Integrations\Phone\PhoneConnector;
use App\Apple\Service\Exception\AttemptBindPhoneCodeException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;

trait Phone
{
    abstract function getPhoneConnector():PhoneConnector;

    /**
     * @param string $rui
     * @return \Saloon\Http\Response
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function getPhoneCodeRequest(string $rui): \Saloon\Http\Response
    {
        return $this->getPhoneConnector()->send(new PhoneCodeRequest($rui));
    }

    /**
     * @param string $url
     * @param int $attempts
     * @return Response
     * @throws AttemptBindPhoneCodeException
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function attemptGetPhoneCode(string $url,int $attempts = 10): string
    {
        for ($i = 0; $i < $attempts; $i++) {
            if ($response = PhoneCodeParser::parse($this->getPhoneCodeRequest($url))) {
                return $response;
            }
        }
        throw new AttemptBindPhoneCodeException("Attempt {$attempts} times failed to get phone code");
    }
}
