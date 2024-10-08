<?php

namespace App\Apple\Service\Client;

use App\Apple\Service\Exception\AttemptBindPhoneCodeException;
use App\Apple\Service\PhoneCodeParser\PhoneCodeParserInterface;
use Illuminate\Http\Client\ConnectionException;

class PhoneCodeClient extends BaseClient
{
    protected function defaultOption(): array
    {
        return [
            'timeout' => 30,
            'connect_timeout' => 60,
            'verify' => false,
        ];
    }

    /**
     * 通过第三方获取手机接受验证码
     * @param string $url
     * @param PhoneCodeParserInterface $phoneCodeParser
     * @return Response|null
     * @throws ConnectionException|\Illuminate\Http\Client\RequestException
     */
    public function getPhoneTokenCode(string $url,PhoneCodeParserInterface $phoneCodeParser): ?string
    {
        return $phoneCodeParser->parse(body: $this->request('GET',$url)->body() ?? '');
    }


    /**
     * @param string $url
     * @param PhoneCodeParserInterface $phoneCodeParser
     * @param int $attempts
     * @param int $sleep
     * @return string
     * @throws AttemptBindPhoneCodeException
     * @throws ConnectionException|\Illuminate\Http\Client\RequestException
     */
    public function attemptGetPhoneCode(string $url, PhoneCodeParserInterface $phoneCodeParser, int $attempts = 6, int $sleep = 5): string
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
