<?php

namespace App\Apple\Service\Client;

use App\Apple\Service\Exception\AccountLockoutException;
use App\Apple\Service\Exception\UnauthorizedException;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;

class IdmsaClient extends BaseClient
{

    /**
     * @return array
     */
    protected function defaultOption(): array
    {
        return [
            RequestOptions::COOKIES => $this->cookieJar,
            'base_uri'              => self::BASEURL_IDMSA,
            'timeout'               => $this->getConfig()->getTimeOutInterval(),
            'connect_timeout'       => $this->getConfig()->getModuleTimeOutInSeconds(),
            'verify'                => false,

            RequestOptions::HEADERS => [
                'X-Apple-Widget-Key'          => $this->getConfig()->getServiceKey(),
                'X-Apple-OAuth-Redirect-URI'  => self::BASEURL_APPLEID,
                'X-Apple-OAuth-Client-Id'     => $this->getConfig()->getServiceKey(),
                'X-Apple-OAuth-Client-Type'   => 'firstPartyAuth',
                'x-requested-with'            => 'XMLHttpRequest',
                'X-Apple-OAuth-Response-Mode' => 'web_message',
                'X-APPLE-HC'                  => '1:12:20240626165907:82794b5d498b7d7dc29740b23971ded5::4824',
                'X-Apple-Domain-Id'           => '1',
                'Origin'                      => self::BASEURL_IDMSA,
                'Referer'                     => self::BASEURL_IDMSA,
                'Accept'                      => 'application/json, text/javascript, */*; q=0.01',
                'Accept-Language'             => 'zh-CN,en;q=0.9,zh;q=0.8',
                'User-Agent'                  => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36',
                'Content-Type'                => 'application/json',
                'Priority'                    => 'u=1, i',
                'Sec-Ch-Ua'                   => "Chromium;v=124, Google Chrome;v=124",
                'Sec-Ch-Ua-Mobile'            => '?0',
                'Sec-Ch-Ua-Platform'          => 'Windows',
                'Connection'                  => 'Keep-Alive',
                'X-Apple-I-TimeZone'          => 'Asia/Shanghai',
                'Sec-Fetch-Site'              => 'same-origin',
                'Sec-Fetch-Mode'              => 'cors',
                'Sec-Fetch-Dest'              => 'empty',
            ],
        ];
    }

    protected function buildUUid(): string
    {
        return sprintf('auth-%s', uniqid('', true));
    }

    /**
     * 获取授权页面
     * @return Response
     * @throws ConnectionException|RequestException
     */
    public function authAuthorizeSignin(): Response
    {
        return $this->request('GET', '/appleauth/auth/authorize/signin', [
            RequestOptions::QUERY   => [
                'frame_id'      => $this->buildUUid(),
                'skVersion'     => '7',
                'iframeId'      => $this->buildUUid(),
                'client_id'     => $this->getConfig()->getServiceKey(),
                'redirect_uri'  => $this->getConfig()->getApiUrl(),
                'response_type' => 'code',
                'response_mode' => 'web_message',
                'state'         => $this->buildUUid(),
                'authVersion'   => 'latest',
            ],
            RequestOptions::HEADERS => [
                'Sec-Fetch-Site' => 'same-origin',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Dest' => 'iframe',
            ],
        ]);
    }

    /**
     * 双重认证首页
     * @return Response
     * @throws ConnectionException|RequestException
     */
    public function auth(): Response
    {
        return $this->request('GET', '/appleauth/auth', [
            RequestOptions::HEADERS     => [
                'X-Apple-ID-Session-Id'   => $this->user->getHeader('X-Apple-ID-Session-Id') ?? '',
                'X-Apple-Auth-Attributes' => $this->user->getHeader('X-Apple-Auth-Attributes') ?? '',
                'Accept'                  => 'text/html',
                'Content-Type'            => 'application/json',
            ],
            RequestOptions::HTTP_ERRORS => true, // 启用 HTTP 错误处理
        ]);
    }

    /**
     * 授权登录(账号密码登录也可以用手机号码登录)
     * @param string $accountName
     * @param string $password
     * @param bool $rememberMe
     * @return Response
     * @throws ConnectionException
     * @throws RequestException
     */
    public function login(string $accountName, string $password, bool $rememberMe = true): Response
    {

        $response = $this->request('post', '/appleauth/auth/signin?isRememberMeEnabled=true', [
            RequestOptions::JSON        => [
                'accountName' => $accountName,
                'password'    => $password,
                'rememberMe'  => $rememberMe,
            ],
            RequestOptions::HEADERS     => [
                'X-Apple-OAuth-Redirect-URI'  => $this->getConfig()->getApiUrl(),
                'X-Apple-OAuth-Client-Id'     => $this->getConfig()->getServiceKey(),
                'X-Apple-OAuth-Client-Type'   => 'firstPartyAuth',
                'x-requested-with'            => 'XMLHttpRequest',
                'X-Apple-OAuth-Response-Mode' => 'web_message',
                'X-APPLE-HC'                  => '1:11:20240629164439:4e19d05de1614b4ea7746036705248f0::1979',
                // todo 动态数据
                'X-Apple-Domain-Id'           => '1',
            ],
            RequestOptions::HTTP_ERRORS => false,
        ]);

        $response->throwIf(function ($re) use ($response) {

            if (403 == $response->status()) {
                throw new AccountLockoutException($response->getFirstErrorMessage(), $response->status());
            }

            if (409 == $response->status()) {
                return false;
            }

            return true;
        });

        return $response;
    }

    /**
     * 验证手机验证码
     * @param int $id
     * @param string $code
     * @return Response
     * @throws ConnectionException|RequestException
     */
    public function validatePhoneSecurityCode(string $code, int $id = 1): Response
    {
        return $this->request('post', '/appleauth/auth/verify/phone/securitycode', [
            RequestOptions::JSON        => [
                'phoneNumber'  => [
                    'id' => $id,
                ],
                'securityCode' => [
                    'code' => $code,
                ],
                'mode'         => 'sms',
            ],
            RequestOptions::HEADERS     => [
                'X-Apple-ID-Session-Id'   => $this->user->getHeader('X-Apple-ID-Session-Id') ?? '',
                'X-Apple-Auth-Attributes' => $this->user->getHeader('X-Apple-Auth-Attributes') ?? '',
            ],
            RequestOptions::HTTP_ERRORS => false,
        ]);
    }

    /**
     * 重新发送验证码（邮箱验证码）
     * @return Response
     * @throws UnauthorizedException|ConnectionException
     */
    public function sendSecurityCode(): Response
    {
        $response = $this->request('PUT', '/appleauth/auth/verify/trusteddevice/securitycode', [
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::HEADERS     => [
                'X-Apple-ID-Session-Id'   => $this->user->getHeader('X-Apple-ID-Session-Id') ?? '',
                'X-Apple-Auth-Attributes' => $this->user->getHeader('X-Apple-Auth-Attributes') ?? '',
            ],
        ]);

        if (!in_array($response->status(), [202, 412])) {
            throw new UnauthorizedException($response->getFirstErrorMessage(), $response->status());
        }

        return $response;
    }

    /**
     * 验证安全代码
     * @param string $code
     * @return Response
     * @throws ConnectionException
     */
    public function validateSecurityCode(string $code): Response
    {
        return $this->request('post', '/appleauth/auth/verify/trusteddevice/securitycode', [
            RequestOptions::JSON        => [
                'securityCode' => [
                    'code' => $code,
                ],
            ],
            RequestOptions::HEADERS     => [
                'X-Apple-ID-Session-Id'   => $this->user->getHeader('X-Apple-ID-Session-Id') ?? '',
                'X-Apple-Auth-Attributes' => $this->user->getHeader('X-Apple-Auth-Attributes') ?? '',
            ],
            RequestOptions::HTTP_ERRORS => false,
        ]);
    }

    /**
     * 发送手机验证码
     * @param int $id
     * @return Response
     * @throws ConnectionException
     */
    public function sendPhoneSecurityCode(int $id): Response
    {
        return $this->request('put', '/appleauth/auth/verify/phone', [
            RequestOptions::JSON    => [
                'phoneNumber' => [
                    'id' => $id,
                ],
                'mode'        => 'sms',
            ],
            RequestOptions::HEADERS => [
                'X-Apple-ID-Session-Id'   => $this->user->getHeader('X-Apple-ID-Session-Id') ?? '',
                'X-Apple-Auth-Attributes' => $this->user->getHeader('X-Apple-Auth-Attributes') ?? '',
            ],
        ]);
    }

    /**
     * @return Response
     * @throws ConnectionException|RequestException
     */
    public function appleAuthRepairComplete(): Response
    {
        return $this->request('POST', '/appleauth/auth/repair/complete', [
            RequestOptions::HEADERS     => [
                'X-Apple-ID-Session-Id'        => $this->cookieJar->getCookieByName('aasp')?->getValue(),
                'X-Apple-Auth-Attributes'      => $this->user->getHeader('X-Apple-Auth-Attributes') ?? '',
                'X-Apple-Repair-Session-Token' => $this->user->getHeader('X-Apple-Repair-Session-Token') ?? '',
            ],
            RequestOptions::HTTP_ERRORS => false,
        ]);
    }

    /**
     * @param string $a
     * @param string $account
     * @return Response
     * @throws ConnectionException
     * @throws RequestException
     */
    public function signinInit(string $a, string $account): Response
    {
        return $this->request('POST', '/appleauth/auth/signin/init', [
            RequestOptions::JSON    => [
                'a'=>$a,
                'accountName' => $account,
                'protocols' => ['s2k','s2k_fo'],
            ],
        ]);
    }

    /**
     * @param string $account
     * @param string $m1
     * @param string $m2
     * @param string $c
     * @param bool $rememberMe
     * @return Response
     * @throws ConnectionException
     * @throws RequestException
     */
    public function complete(string $account, string $m1, string $m2, string $c, bool $rememberMe = false): Response
    {
        return $this->request('POST', '/appleauth/auth/signin/complete?isRememberMeEnabled=true', [
            RequestOptions::JSON    => [
                'accountName' => $account,
                'm1'    => $m1,
                'm2'  => $m2,
                'c'  => $c,
                'rememberMe'  => $rememberMe,
            ],
        ]);
    }
}
