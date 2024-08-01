<?php

namespace App\Apple\Service\Client;

use App\Apple\Service\Exception\AccountLockoutException;
use App\Apple\Service\Exception\UnauthorizedException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

class IdmsaClient extends BaseClient
{

    /**
     * @return Client
     * @throws UnauthorizedException
     */
    protected function createClient(): Client
    {
        if (empty($config = $this->user->getConfig())){
            throw new UnauthorizedException('Config is empty');
        }

        return $this->clientFactory->create($this->user,[
            RequestOptions::COOKIES => $this->cookieJar,
            'base_uri'              => self::BASEURL_IDMSA,
            'timeout'               => $config->getTimeOutInterval(),
            'connect_timeout'       => $config->getModuleTimeOutInSeconds(),
            'verify'                => false,
            'proxy'                 => $this->getProxyResponse()->getUrl(),  // 添加这行

            RequestOptions::HEADERS => [
                'X-Apple-Widget-Key'          => $config->getServiceKey(),
                'X-Apple-OAuth-Redirect-URI'  => self::BASEURL_APPLEID,
                'X-Apple-OAuth-Client-Id'     => $config->getServiceKey(),
                'X-Apple-OAuth-Client-Type'   => 'firstPartyAuth',
                'x-requested-with'            => 'XMLHttpRequest',
                'X-Apple-OAuth-Response-Mode' => 'web_message',
                'X-APPLE-HC'                  => '1:12:20240626165907:82794b5d498b7d7dc29740b23971ded5::4824',
                'X-Apple-Domain-Id'           => '1',
                'Origin'                      => self::BASEURL_IDMSA,
                'Referer'                     => self::BASEURL_IDMSA,
                'Accept'                      => 'application/json, text/javascript, */*; q=0.01',
                'Accept-Language'             => 'zh-CN,zh;q=0.9',
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
        ]);
    }

    protected function buildUUid(): string
    {
        return sprintf('auth-%s', uniqid());
    }

    /**
     * 获取授权页面
     * @return Response
     * @throws GuzzleException
     */
    public function authAuthorizeSignin(): Response
    {
        return $this->request('GET', '/appleauth/auth/authorize/signin', [
            RequestOptions::QUERY   => [
                'frame_id'      => $this->buildUUid(),
                'skVersion'     => '7',
                'iframeId'      => $this->buildUUid(),
                'client_id'     => $this->user->getConfig()->getServiceKey(),
                'redirect_uri'  => $this->user->getConfig()->getApiUrl(),
                'response_type' => 'code',
                'response_mode' => 'web_message',
                'state'         => $this->buildUUid(),
                'authVersion'   => 'latest',
            ],
            RequestOptions::HEADERS => [
                'Sec-Fetch-Site'            => 'same-origin',
                'Sec-Fetch-Mode'            => 'navigate',
                'Sec-Fetch-Dest'            => 'iframe',
            ],
        ]);
    }

    /**
     * 双重认证首页
     * @return Response
     * @throws GuzzleException
     */
    public function auth(): Response
    {
        try {

            return $this->request('GET', '/appleauth/auth', [
                RequestOptions::HEADERS => [
                    'X-Apple-ID-Session-Id'   => $this->user->getHeader('X-Apple-ID-Session-Id') ?? '',
                    'X-Apple-Auth-Attributes' => $this->user->getHeader('X-Apple-Auth-Attributes') ?? '',
                    'Accept'                  => 'application/json, text/javascript, */*; q=0.01',
                    'Content-Type'            => 'application/json',
                ],
                RequestOptions::HTTP_ERRORS => true, // 启用 HTTP 错误处理
            ]);

        } catch (ClientException $e) {

            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            if(423 === $statusCode){
                return new Response($response,$statusCode,$responseBody);
            }
            throw $e;
        }
    }

    /**
     * 授权登录(账号密码登录也可以用手机号码登录)
     * @param string $accountName
     * @param string $password
     * @param bool $rememberMe
     * @return Response
     * @throws UnauthorizedException
     * @throws GuzzleException|AccountLockoutException
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
                'X-Apple-Widget-Key'          => $this->user->getConfig()?->getServiceKey(),
                'X-Apple-OAuth-Redirect-URI'  => 'https://appleid.apple.com',
                'X-Apple-OAuth-Client-Id'     => $this->user->getConfig()?->getServiceKey(),
                'X-Apple-OAuth-Client-Type'   => 'firstPartyAuth',
                'x-requested-with'            => 'XMLHttpRequest',
                'X-Apple-OAuth-Response-Mode' => 'web_message',
                'X-APPLE-HC'                  => '1:11:20240629164439:4e19d05de1614b4ea7746036705248f0::1979', // todo 动态数据
                'X-Apple-Domain-Id'           => '1',
            ],
            RequestOptions::HTTP_ERRORS => false,
        ]);

        if (403 === $response->getStatus()){
            throw new AccountLockoutException($response->getFirstErrorMessage(), $response->getStatus());
        }

        if (409 !== $response->getStatus()) {
            throw new UnauthorizedException($response->getFirstErrorMessage(), $response->getStatus());
        }

        return $response;
    }

    /**
     * 验证手机验证码（登录）
     * @param int $id
     * @param string $code
     * @return Response
     * @throws GuzzleException
     */
    public function validatePhoneSecurityCode(string $code, int $id = 1): Response
    {
        $response = $this->request('post', '/appleauth/auth/verify/phone/securitycode', [
            RequestOptions::JSON => [
                'phoneNumber'  => [
                    'id' => $id,
                ],
                'securityCode' => [
                    'code' => $code,
                ],
                'mode'         => 'sms',
            ],
            RequestOptions::HEADERS => [
                'X-Apple-ID-Session-Id'   => $this->user->getHeader('X-Apple-ID-Session-Id') ?? '',
                'X-Apple-Auth-Attributes' => $this->user->getHeader('X-Apple-Auth-Attributes') ?? '',
            ],
        ]);

        //获取所有 Cookie
//        $cookies = $this->cookieJar->getIterator();

        return $response;

    }

    /**
     * 重新发送验证码（邮箱验证码）
     * @return Response
     * @throws UnauthorizedException|GuzzleException
     */
    public function sendSecurityCode(): Response
    {
        $response = $this->request('PUT', '/appleauth/auth/verify/trusteddevice/securitycode', [
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::HEADERS => [
                'X-Apple-ID-Session-Id'   => $this->user->getHeader('X-Apple-ID-Session-Id') ?? '',
                'X-Apple-Auth-Attributes' => $this->user->getHeader('X-Apple-Auth-Attributes') ?? '',
            ],
        ]);

        if (!in_array($response->getStatus(), [202, 412])) {
            throw new UnauthorizedException($response->getFirstErrorMessage(), $response->getStatus());
        }

        return $response;
    }

    /**
     * 验证安全代码
     * @param string $code
     * @return Response
     * @throws GuzzleException
     */
    public function validateSecurityCode(string $code): Response
    {
        return $this->request('post', '/appleauth/auth/verify/trusteddevice/securitycode', [
            RequestOptions::JSON    => [
                'securityCode' => [
                    'code' => $code,
                ],
            ],
            RequestOptions::HEADERS => [
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
     * @throws GuzzleException
     */
    public function sendPhoneSecurityCode(int $id): Response
    {
        return $this->request('put', '/appleauth/auth/verify/phone', [
            RequestOptions::JSON => [
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
    // Other IDMSA specific methods...
}
