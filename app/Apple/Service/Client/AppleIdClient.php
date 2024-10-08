<?php

namespace App\Apple\Service\Client;

use App\Apple\Service\Exception\UnauthorizedException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;

class AppleIdClient extends BaseClient
{

    protected function defaultOption(): array
    {
        return [
            'base_uri'              => self::BASEURL_APPLEID,
            'timeout'               => 30,
            'connect_timeout'       => 60,
            'verify'                => false,
            RequestOptions::COOKIES => $this->cookieJar,
            RequestOptions::HEADERS => [
                'Connection'                => 'Keep-Alive',
                'Content-Type'              => 'application/json',
                'Accept'                    => 'application/json, text/plain, */*',
                'Accept-Language'           => 'zh-CN,en;q=0.9,zh;q=0.8',
                'X-Apple-I-Request-Context' => 'ca',
                'X-Apple-I-TimeZone'        => 'Asia/Shanghai',
                'Sec-Fetch-Site'            => 'same-origin',
                'Sec-Fetch-Mode'            => 'cors',
                'Sec-Fetch-Dest'            => 'empty',
                'User-Agent'            => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            ],
        ];
    }

    /**
     * 获取token
     * @return Response
     * @throws ConnectionException|\Illuminate\Http\Client\RequestException
     */
    public function accountManageToken(): Response
    {
        return $this->request('get', '/account/manage/gs/ws/token');
    }

    /**
     * 验证密码
     * @param string $password
     * @return Response
     * @throws ConnectionException|\Illuminate\Http\Client\RequestException
     */
    public function password(string $password): Response
    {
        return $this->request('POST', '/authenticate/password', [
            RequestOptions::JSON => [
                'password' => $password,
            ],
        ]);
    }

    /**
     * 绑定手机号码
     * @param string $phoneNumber
     * @param string $countryCode
     * @param string $countryDialCode
     * @param bool $nonFTEU
     * @return Response
     * @throws ConnectionException|\Illuminate\Http\Client\RequestException
     */
    public function bindPhoneSecurityVerify(
        string $phoneNumber,
        string $countryCode,
        string $countryDialCode,
        bool $nonFTEU = true
    ): Response {

        return $this->request('POST', '/account/manage/security/verify/phone', [

            RequestOptions::JSON        => [
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
            RequestOptions::HTTP_ERRORS => false,
        ]);
    }

    /**
     * 获取 bootstrap
     * @return Response
     * @throws ConnectionException|\Illuminate\Http\Client\RequestException
     */
    public function bootstrap(): Response
    {
        return $this->request('get', '/bootstrap/portal');
    }


    /**
     * 验证手机验证码(绑定手机号码)
     * @param int $id
     * @param string $phoneNumber
     * @param string $countryCode
     * @param string $countryDialCode
     * @param string $code
     * @return Response
     * @throws ConnectionException|\Illuminate\Http\Client\RequestException
     */
    public function manageVerifyPhoneSecurityCode(
        int $id,
        string $phoneNumber,
        string $countryCode,
        string $countryDialCode,
        string $code
    ): Response {
        return $this->request('POST', '/account/manage/security/verify/phone/securitycode', [
            RequestOptions::JSON => [
                'phoneNumberVerification' => [
                    'phoneNumber'  => [
                        'id'              => $id,
                        'number'          => $phoneNumber,
                        'countryCode'     => $countryCode,
                        'countryDialCode' => $countryDialCode,
                    ],
                    'securityCode' => [
                        'code' => $code,
                    ],
                    'mode'         => 'sms',
                ],
            ],
        ]);
    }

    /**
     * @return Response
     * @throws GuzzleException|ConnectionException|\Illuminate\Http\Client\RequestException
     */
    public function managePrivacyAccept(): Response
    {
        return $this->request('OPTIONS', '/account/manage/privacy/accept', [
            RequestOptions::HEADERS => [
                'X-Apple-Widget-Key'    => $this->getConfig()->getServiceKey(),
                'X-Apple-ID-Session-Id' => $this->cookieJar->getCookieByName('aidsp')?->getValue(),
                'X-Apple-OAuth-Context' => $this->user->getHeader('X-Apple-OAuth-Context') ?? '',
                'X-Apple-Session-Token' => $this->user->getHeader('X-Apple-Session-Token') ?? '',
            ],
        ]);
    }

    /**
     * @return Response
     * @throws GuzzleException|ConnectionException|\Illuminate\Http\Client\RequestException
     */
    public function manageRepairOptions(): Response
    {
        return $this->request('GET', '/account/manage/repair/options', [
            RequestOptions::HEADERS => [
                'X-Apple-Widget-Key'    => $this->getConfig()->getServiceKey(),
                'X-Apple-ID-Session-Id' => $this->cookieJar->getCookieByName('aidsp')?->getValue(),
                'X-Apple-OAuth-Context' => $this->user->getHeader('X-Apple-OAuth-Context') ?? '',
                'X-Apple-Session-Token' => $this->user->getHeader('X-Apple-Repair-Session-Token') ?? '',
            ],
        ]);
    }
}
