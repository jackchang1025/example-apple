<?php

namespace App\Apple\Service\Client;

use App\Apple\Service\Exception\AttemptBindPhoneCodeException;
use App\Apple\Service\PhoneCodeParser\PhoneCodeParserInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class PhoneCodeClient extends BaseClient
{
    protected function createClient(): Client
    {
        return $this->clientFactory->create(user: $this->user,additionalConfig: [
           'timeout' => 30,
            'connect_timeout' => 60,
            'verify' => false,
        ]);
    }

    /**
     * 通过第三方获取手机接受验证码
     * @param string $url
     * @param PhoneCodeParserInterface $phoneCodeParser
     * @return Response|null
     * @throws GuzzleException
     */
    public function getPhoneTokenCode(string $url,PhoneCodeParserInterface $phoneCodeParser): ?Response
    {
        $response = $this->getClient()->get($url);

        if (empty($body = (string) $response->getBody())) {
            return null;
        }

        if (($code = $phoneCodeParser->parse(body: $body)) === null) {
            return null;
        }

        return new Response(response: $response,status:$response->getStatusCode(),data:  ['code' => $code]);
    }


    /**
     * @param string $url
     * @param PhoneCodeParserInterface $phoneCodeParser
     * @param int $attempts
     * @param int $sleep
     * @return Response|null
     * @throws AttemptBindPhoneCodeException
     * @throws GuzzleException
     */
    public function attemptGetPhoneCode(string $url, PhoneCodeParserInterface $phoneCodeParser, int $attempts = 6, int $sleep = 5): ?Response
    {
        for ($i = 0; $i < $attempts; $i++) {
            if ($response = $this->getPhoneTokenCode($url, $phoneCodeParser)) {
                return $response;
            }
            sleep($sleep);
        }
        throw new AttemptBindPhoneCodeException("Attempt {$attempts} times failed to get phone code");
    }
}
