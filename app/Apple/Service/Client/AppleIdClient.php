<?php

namespace App\Apple\Service\Client;

use App\Apple\Service\Exception\UnauthorizedException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

class AppleIdClient extends BaseClient
{

    protected function createClient(): Client
    {
       return $this->clientFactory->create(user: $this->user,additionalConfig: [
            'base_uri'              => self::BASEURL_APPLEID,
            'timeout'               => 30,
            'connect_timeout'       => 60,
            'verify'                => false,
            'proxy'                 => $this->getProxyUrl(),  // 添加这行
            RequestOptions::COOKIES => $this->cookieJar,
            RequestOptions::HEADERS => [
                'Connection'                => 'Keep-Alive',
                'Content-Type'              => 'application/json',
                'Accept'                    => 'application/json, text/plain, */*',
                'Accept-Language'           => 'zh-CN,zh;q=0.9',
                'X-Apple-I-Request-Context' => 'ca',
                'X-Apple-I-TimeZone'        => 'Asia/Shanghai',
                'Sec-Fetch-Site'            => 'same-origin',
                'Sec-Fetch-Mode'            => 'cors',
                'Sec-Fetch-Dest'            => 'empty',
            ],
        ]);
    }

    /**
     * 获取token
     * @return Response
     * @throws GuzzleException
     */
    public function accountManageToken(): Response
    {
        return $this->request('get', '/account/manage/gs/ws/token');
    }

    /**
     * 验证密码
     * @param string $password
     * @return Response
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function password(string $password): Response
    {
        $response = $this->request('POST', '/authenticate/password', [
            RequestOptions::JSON        => [
                'password' => $password,
            ],
            RequestOptions::HEADERS     => [
            ],
            RequestOptions::HTTP_ERRORS => false,
        ]);

        if ($response->getStatus() !== 204) {
            throw new UnauthorizedException($response->getFirstErrorMessage(), $response->getStatus());
        }

        return $response;

    }

    /**
     * 绑定手机号码
     * @param string $phoneNumber
     * @param string $countryCode
     * @param string $countryDialCode
     * @param bool $nonFTEU
     * @return Response
     * @throws GuzzleException
     */
    public function bindPhoneSecurityVerify(
        string $phoneNumber,
        string $countryCode,
        string $countryDialCode,
        bool $nonFTEU = true
    ): Response {

        return $this->request('POST', '/account/manage/security/verify/phone', [

            RequestOptions::JSON    => [
                'phoneNumberVerification' => [
                    'phoneNumber' => [
                        'countryCode'     => $countryCode,
                        'number'          => $phoneNumber,
                        'countryDialCode' => $countryDialCode,
                        'nonFTEU'         => $nonFTEU,
                    ],
                    'mode'        => 'sms',
                ],
            ],
            RequestOptions::HEADERS => [

            ],
        ]);
    }

    /**
     * 获取 bootstrap
     * @return Response
     * @throws GuzzleException
     */
    public function bootstrap(): Response
    {
        return $this->request('get','/bootstrap/portal');
    }

    /**
     * 验证手机验证码(绑定手机号码)
     * @param int $id
     * @param string $phoneNumber
     * @param string $countryCode
     * @param string $countryDialCode
     * @param string $code
     * @return Response
     * @throws GuzzleException
     */
    public function manageVerifyPhoneSecurityCode(int $id,string $phoneNumber,string $countryCode,string $countryDialCode,string $code): Response
    {
        return $this->request('POST', '/account/manage/security/verify/phone/securitycode', [
            RequestOptions::JSON => [
                'phoneNumberVerification' => [
                    'phoneNumber' => [
                        'id' => $id,
                        'number' => $phoneNumber,
                        'countryCode' => $countryCode,
                        'countryDialCode' => $countryDialCode,
                    ],
                    'securityCode' => [
                        'code' => $code,
                    ],
                    'mode' => 'sms',
                ]
            ]
        ]);
    }
    // Other AppleID specific methods...

}
