<?php

declare(strict_types=1);

namespace App\Apple\Service;

use App\Apple\Service\Exception\AttemptBindPhoneCodeException;
use App\Apple\Service\Exception\LockedException;
use App\Apple\Service\Exception\BindPhoneCodeException;
use App\Apple\Service\Exception\UnauthorizedException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Uuid\Exception\UnableToBuildUuidException;
use Symfony\Component\HttpFoundation\Cookie;

class HttpClientBak
{

    protected ?Client $client = null;

    protected ?Config $config = null;

    protected ?Client $appleidClient = null;

    const string BASEURL_APPLEID = 'https://appleid.apple.com';

    const string BASEURL_IDMSA = 'https://idmsa.apple.com';

    //401 Unauthorized

    public function __construct(
        protected ClientFactory $clientFactory,
        protected CookieJarInterface $cookieJar,
        protected LoggerInterface $logger,
        protected User $user,
    ) {
    }

    /**
     * @param string $username
     * @param string $password
     * @return Response
     * @throws UnauthorizedException
     * @throws GuzzleException|LockedException
     */
    public function signin(string $username, string $password): Response
    {
        $this->bootstrap();

        $this->authAuthorizeSignin();

        $response = $this->login($username, $password);

        $authResponse = $this->auth();
        if(empty($authResponse->getData())){
            throw new UnauthorizedException('Unauthorized',$authResponse->getStatus());
        }

        $this->user->setPhoneInfo($authResponse->getData());

        return $authResponse;
    }


    /**
     * @return Client
     */
    public function getClient(): Client
    {
        if ($this->client === null) {

            $config = $this->user->getConfig();

            $this->client = $this->clientFactory->create([
                RequestOptions::COOKIES => $this->cookieJar,
                'base_uri'              => self::BASEURL_IDMSA,
                'timeout'               => $config->getTimeOutInterval(),
                'connect_timeout'       => $config->getModuleTimeOutInSeconds(),
                'verify'                => false,

                RequestOptions::HEADERS => [
                    'X-Apple-Widget-Key'          => $config->getServiceKey(),
                    'X-Apple-OAuth-Redirect-URI'  => self::BASEURL_APPLEID,
                    'X-Apple-OAuth-Client-Id'     => $config->getServiceKey(),
                    'X-Apple-OAuth-Client-Type'   => 'firstPartyAuth',
                    'x-requested-with'            => 'XMLHttpRequest',
                    'X-Apple-OAuth-Response-Mode' => 'web_message',
                    //                                        'X-APPLE-HC'                  => '1:12:20240626165907:82794b5d498b7d7dc29740b23971ded5::4824',
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

        return $this->client;
    }

    protected function createConfig(array $config = []): Config
    {
        return new Config(
            $config['apiUrl'] ?? '',
            $config['serviceKey'] ?? '',
            $config['serviceUrl'] ?? '',
            $config['environment'] ?? '',
            $config['timeOutInterval'] ?? '',
            $config['moduleTimeOutInSeconds'] ?? 0,
            $config['$XAppleIDSessionId'] ?? null,
            $config['pageFeatures'] ?? [],
            $config['signoutUrls'] ?? []
        );
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return Response
     * @throws GuzzleException|UnauthorizedException
     */
    public function request(string $method, string $uri, array $options = []): Response
    {
        $response = $this->getClient()->request($method, $uri, $options);

        return $this->parseJsonResponse($response);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return Response
     * @throws GuzzleException
     */
    public function appleidRequest(string $method, string $uri, array $options = []): Response
    {
        $response = $this->getAppleidClient()->request($method, $uri, $options);

        return $this->parseJsonResponse($response);
    }

    //解析 返回数据
    public function parseJsonResponse(ResponseInterface $response): Response
    {
        $body = (string) $response->getBody();

        // 去除可能存在的额外引号
        $body = trim($body, '"');

        // 解码转义的引号
        $body = stripslashes($body);

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // JSON 解析错误处理
//            var_dump('JSON decode error', json_last_error_msg());
            //记录日志
            $this->logger->error('JSON decode error: ', ['message' => json_last_error_msg(),'body' => $body]);
            $data = [];
        }

        if (!is_array($data)) {
            $data = [$data];
        }

        return new Response(
            response: $response,
            status: $response->getStatusCode(),
            data: $data
        );
    }

    public function buildUUid(): string
    {
        return sprintf('auth-%s', uniqid());
    }

    public function getAppleidClient(): ?Client
    {
        if ($this->appleidClient === null) {
            $this->appleidClient = $this->clientFactory->create(additionalConfig: [
                'base_uri'              => self::BASEURL_APPLEID,
                'timeout'               => 30,
                'connect_timeout'       => 60,
                'verify'                => false,
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

        return $this->appleidClient;
    }


    /**
     * 获取 bootstrap
     * @return Response
     * @throws GuzzleException|UnauthorizedException
     */
    public function bootstrap(): Response
    {
        //GET https://appleid.apple.com/bootstrap/portal HTTP/1.1
        //Connection: Keep-Alive
        //Content-Type: application/json
        //Accept: application/json, text/plain, */*
        //Accept-Language: zh-CN,zh;q=0.9
        //Host: appleid.apple.com
        //Referer: https://appleid.apple.com/
        //User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36
        //X-Apple-I-Request-Context: ca
        //X-Apple-I-TimeZone: Asia/Shanghai
        //Sec-Fetch-Site: same-origin
        //Sec-Fetch-Mode: cors
        //Sec-Fetch-Dest: empty

        $response = $this->getAppleidClient()->get('/bootstrap/portal');
        $response = $this->parseJsonResponse($response);

        if (empty($data = $response->getData())) {
            throw new UnauthorizedException('未获取到配置信息');
        }

        $this->user->setConfig($this->createConfig($data));

        return $response;

        //HTTP/1.1 200
        //Server: Apple
        //Date: Sat, 29 Jun 2024 16:44:40 GMT
        //Content-Type: application/json;charset=UTF-8
        //Transfer-Encoding: chunked
        //Connection: keep-alive
        //X-Apple-I-Request-ID: e0e98970-3636-11ef-9afc-6d4d72df6b5e
        //Set-Cookie: idclient=web; Domain=appleid.apple.com; Path=/; Secure; HttpOnly
        //X-BuildVersion: R12_1
        //X-FRAME-OPTIONS: DENY
        //X-Content-Type-Options: nosniff
        //X-XSS-Protection: 1; mode=block
        //Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
        //Content-Security-Policy: default-src 'self' ; connect-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com https://api.apple-cloudkit.com https://feedbackws.apple-cloudkit.com https://*.icloud-content.com https://*.icloud-content.com.cn ; font-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com ; frame-src 'self' https://idmsa.apple.com https://signin.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://familyws.icloud.apple.com  https://apps.apple.com ; img-src 'self' https://www.apple.com https://appleid.cdn-apple.com data: https://*.mzstatic.com https://appleid.apple.com https://*.icloud.com ; media-src data: ; object-src 'none' ; script-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://signin.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://appleid.apple.com ; style-src 'unsafe-inline' 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com ;
        //Referrer-Policy: origin
        //Set-Cookie: dslang=CN-ZH; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: site=CHN; Domain=apple.com; Path=/; Secure; HttpOnly
        //Pragma: no-cache
        //Expires: Thu, 01 Jan 1970 00:00:00 GMT
        //Cache-Control: no-cache
        //Cache-Control: no-store
        //Set-Cookie: aidsp=A615630F6BD1DCFBDB386D5438414FF5BA8FD78888BE9E63DFC673D6BD9D424CDE899BC2E4D841CA936047F7D35189A9F417A36997FFEC2B4DEDD749E2203662E7CBF6460972ABE33AD5E6945B5DEF5F55CD1A7849ECE3C98D41A94CA02AC6D45740C7534ED5FEBF7E27D85E3E292B8B2FD56CA5C16BEAC9; Domain=appleid.apple.com; Path=/; Secure; HttpOnly
        //vary: accept-encoding
        //Host: appleid.apple.com
        //
        //1bd
        //{
        //  "apiUrl" : "https://appleid.apple.com",
        //  "serviceKey" : "af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3",
        //  "serviceUrl" : "https://idmsa.apple.com/appleauth",
        //  "environment" : "idms_prod",
        //  "timeOutInterval" : 15,
        //  "pageFeatures" : {
        //    "shouldShowRichAnimations" : true,
        //    "shouldShowNewCreate" : false
        //  },
        //  "signoutUrls" : [ "https://apps.apple.com/includes/commerce/logout" ],
        //  "moduleTimeOutInSeconds" : 60
        //}
        //0

    }


    /**
     * 获取授权页面
     * @return Response
     * @throws GuzzleException|UnauthorizedException
     */
    public function authAuthorizeSignin(): Response
    {
        //GET https://idmsa.apple.com/appleauth/auth/authorize/signin?
        //frame_id=auth-Z6IiQ4z0-Y677aIMQ-OK6jEEuU-DsjIpXMs-7sKLUz8N
        //&skVersion=7
        //&iframeId=auth-Z6IiQ4z0-Y677aIMQ-OK6jEEuU-DsjIpXMs-7sKLUz8N
        //&client_id=af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //&redirect_uri=https://appleid.apple.com
        //&response_type=code
        //&response_mode=web_message
        //&state=auth-Z6IiQ4z0-Y677aIMQ-OK6jEEuU-DsjIpXMs-7sKLUz8N
        //&authVersion=latest
        // HTTP/1.1
        //Connection: Keep-Alive
        //Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9
        //Accept-Language: zh-CN,zh;q=0.9
        //Cookie: idclient=web; dslang=CN-ZH; site=CHN; aidsp=A615630F6BD1DCFBDB386D5438414FF5BA8FD78888BE9E63DFC673D6BD9D424CDE899BC2E4D841CA936047F7D35189A9F417A36997FFEC2B4DEDD749E2203662E7CBF6460972ABE33AD5E6945B5DEF5F55CD1A7849ECE3C98D41A94CA02AC6D45740C7534ED5FEBF7E27D85E3E292B8B2FD56CA5C16BEAC9
        //Host: idmsa.apple.com
        //Referer: https://appleid.apple.com/
        //User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36
        //Upgrade-Insecure-Requests: 1
        //Sec-Fetch-Site: same-origin
        //Sec-Fetch-Mode: navigate
        //Sec-Fetch-Dest: iframe

        $response = $this->request('GET', '/appleauth/auth/authorize/signin', [
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

        //HTTP/1.1 200
        //Server: Apple
        //Date: Sat, 29 Jun 2024 16:44:41 GMT
        //Content-Type: text/html;charset=UTF-8
        //Transfer-Encoding: chunked
        //Connection: keep-alive
        //X-Apple-I-Request-ID: e1717e58-3636-11ef-8af0-1f556b37c8d4
        //X-FRAME-OPTIONS: ALLOW-FROM https://appleid.apple.com
        //X-Content-Type-Options: nosniff
        //X-XSS-Protection: 1; mode=block
        //Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
        //Content-Security-Policy: default-src 'self' ; child-src blob: ; connect-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://webcourier.sandbox.push.apple.com https://xp-qa.apple.com ; font-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ; frame-src 'self' https://appleid.apple.com https://gsa.apple.com ; img-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://*.mzstatic.com data: https://*.apple.com ; media-src data: ; object-src 'none' ; script-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ; style-src 'unsafe-inline' 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ;  frame-ancestors 'self' https://appleid.apple.com;
        //Referrer-Policy: origin
        //X-BuildVersion: R12
        //scnt: AAAA-kY3QUE5QTQ1OTlGOUJENTMyN0MwREM1REI0Q0NBMENFRjlBMTNGNUJGNzk1MkNBQTE0MUI4RDYyQjMwMjExNzhCNzg3QkZEMUNBQjUxQUMwMjRENjIxMTRCODFCMzgwQ0Y2MDAwQzc3NUVENkM2QUQxQjhDNDgwMzEzQTIyNTAzRjNBRjE1RTFDNDZGNTM0RDRBRDIyQjlBQTJCMTBCNkJBRERGQzk3RkMyMkVFNEVCRDk5MEREN0ZBNjY2MzkyRERBODQyQzRBMTE4NTUwQ0EwRUZBNkQ5MUE4OTJENzQ5NzZEOEQ1RjQ4NDJCRjYzMnwxAAABkGTwMF_jZ1xBY_bj2Zg3qO33cj1mKUsQEEGoekbHKfZ0DnmJKfNPR_Tu1KwzABpVCWGZEPANjIz7pubcRPD1yuyULSlBCJzsxJRjTi2PZEgVApUvwg
        //Set-Cookie: dslang=CN-ZH; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: site=CHN; Domain=apple.com; Path=/; Secure; HttpOnly
        //Pragma: no-cache
        //Expires: Thu, 01 Jan 1970 00:00:00 GMT
        //Cache-Control: no-cache
        //Cache-Control: no-store
        //X-Apple-Auth-Attributes: RUgyeIedCRSPqmkqbiAoJ94ym051fU9/44xeB+BJ//Vigtkll5NOflK2StGTa920zL/O058xe6n/Dzw5IiGpH/4tHcBK5dD0ehpTdblJb7WK/zBHZT3xDpNROSNItpAGuOOJ2xNCkJrFSW7ztKBPvJbxWYoD345yJRq64q8gwY1lY+6CL8kzcpFzMgObW5y/nENvl/65wtOixZqNpJCr33xG3mmu9TuUUIgk8g59rLGgIjKkJ4oBQ31XAw31qe/EpI/pafSM0U1wttlgeyCLABpVCWMms8o=
        //X-Apple-HC-Bits: 11
        //X-Apple-HC-Challenge: 4e19d05de1614b4ea7746036705248f0
        //Set-Cookie: aasp=90E43597BF90D2696A23796E3CC61018B24F9EB5AEC0DD2AAEE771D797488FD8BDA7D500043E312708FCA62BFB491914804E34DA3AA46195836B9D2425F73D395961913F46D7D1E34D76E08934E2C998782410ED910E3EAA1F7B60027950F8B7CF6F01A2FC45B955A27D2A3628EC56DBFF67439E0C1E427D; Domain=idmsa.apple.com; Path=/; Secure; HttpOnly
        //vary: accept-encoding
        //Content-Language: zh-CN-x-lvariant-CHN
        //
        //35df

        return $response;
    }

    /**
     * 授权登录(账号密码登录也可以用手机号码登录)
     * @param string $accountName
     * @param string $password
     * @param bool $rememberMe
     * @return Response
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function login(string $accountName, string $password, bool $rememberMe = true): Response
    {
        //aidsp=C8629F8E0EA2C2A34BEF43D5E6394175FC18219695B0D537434183356E621599195BCFC7DEF7AE5423C2761C146D1DA49BDD35E47B5D9A945A5E5CAC65B7462B51112A4900A06A9ED4A3B9616F8655576090FF6950C4AE3560839E092A6C85A109E6D0CD8257DA74350D963EB48F1CA422199465EE58BE59;
        // aasp=A79FA5C9107AC7D03C84480AB286BDCC19372E561B3BA74D8F54A5FB072F07A52C3B67A65452DFFE4EF19F1D7BC01A07362DB7D611CE512859AE63DBDD639CDF1EB3208A653048AAE784CB161C2BF5F248636CE5B87AF6753F66DBA8273B984EA1099D682D7FD1467983CABAA81244DDBF9041000F6BD9B7

        //POST https://idmsa.apple.com/appleauth/auth/signin?isRememberMeEnabled=true HTTP/1.1
        //Connection: Keep-Alive
        //Content-Type: application/json; Charset=UTF-8
        //Accept: application/json, text/javascript, */*; q=0.01
        //Accept-Language: zh-CN,zh;q=0.9
        //Cookie: idclient=web; dslang=CN-ZH; site=CHN; aidsp=A615630F6BD1DCFBDB386D5438414FF5BA8FD78888BE9E63DFC673D6BD9D424CDE899BC2E4D841CA936047F7D35189A9F417A36997FFEC2B4DEDD749E2203662E7CBF6460972ABE33AD5E6945B5DEF5F55CD1A7849ECE3C98D41A94CA02AC6D45740C7534ED5FEBF7E27D85E3E292B8B2FD56CA5C16BEAC9; aasp=90E43597BF90D2696A23796E3CC61018B24F9EB5AEC0DD2AAEE771D797488FD8BDA7D500043E312708FCA62BFB491914804E34DA3AA46195836B9D2425F73D395961913F46D7D1E34D76E08934E2C998782410ED910E3EAA1F7B60027950F8B7CF6F01A2FC45B955A27D2A3628EC56DBFF67439E0C1E427D
        //Host: idmsa.apple.com
        //Referer: https://idmsa.apple.com/
        //User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36
        //X-Apple-Widget-Key: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Apple-OAuth-Redirect-URI: https://appleid.apple.com
        //X-Apple-OAuth-Client-Id: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Apple-OAuth-Client-Type: firstPartyAuth
        //x-requested-with: XMLHttpRequest
        //X-Apple-OAuth-Response-Mode: web_message
        //X-APPLE-HC: 1:11:20240629164439:4e19d05de1614b4ea7746036705248f0::1979
        //X-Apple-Domain-Id: 1
        //Origin: https://idmsa.apple.com
        //Sec-Fetch-Site: same-origin
        //Sec-Fetch-Mode: cors
        //Sec-Fetch-Dest: empty
        //Content-Length: 82
        //
        //{"accountName":"homer1458796@hotmail.com","rememberMe":true,"password":"Aa258658"}

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


        if (409 !== $response->getStatus()) {
            throw new UnauthorizedException($response->getFirstErrorMessage(), $response->getStatus());
        }

        return $response;
        //HTTP/1.1 409
        //Server: Apple
        //Date: Sat, 29 Jun 2024 16:44:41 GMT
        //Content-Type: application/json;charset=UTF-8
        //Transfer-Encoding: chunked
        //Connection: keep-alive
        //X-Apple-I-Request-ID: e1e6d5b6-3636-11ef-8664-fb9eb9b57915
        //X-FRAME-OPTIONS: DENY
        //X-Content-Type-Options: nosniff
        //X-XSS-Protection: 1; mode=block
        //Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
        //Content-Security-Policy: default-src 'self' ; child-src blob: ; connect-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://webcourier.sandbox.push.apple.com https://xp-qa.apple.com ; font-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ; frame-src 'self' https://appleid.apple.com https://gsa.apple.com ; img-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://*.mzstatic.com data: https://*.apple.com ; media-src data: ; object-src 'none' ; script-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ; style-src 'unsafe-inline' 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ;
        //Referrer-Policy: origin
        //X-BuildVersion: R12
        //scnt: AAAA-jkwRTQzNTk3QkY5MEQyNjk2QTIzNzk2RTNDQzYxMDE4QjI0RjlFQjVBRUMwREQyQUFFRTc3MUQ3OTc0ODhGRDhCREE3RDUwMDA0M0UzMTI3MDhGQ0E2MkJGQjQ5MTkxNDgwNEUzNERBM0FBNDYxOTU4MzZCOUQyNDI1RjczRDM5NTk2MTkxM0Y0NkQ3RDFFMzRENzZFMDg5MzRFMkM5OTg3ODI0MTBFRDkxMEUzRUFBMUY3QjYwMDI3OTUwRjhCN0NGNkYwMUEyRkM0NUI5NTVBMjdEMkEzNjI4RUM1NkRCRkY2NzQzOUUwQzFFNDI3RHwxAAABkGTwM2jGyj59uUMIYScqOpI344UW8UIOMM5V0jWnuQtqkyLi5603scL95qhGABpVCN5FlS9iTpRFArfyw3F8i1Ntz6RLjnW23hsBHXlCy_J4btAbcg
        //Set-Cookie: dslang=CN-ZH; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: site=CHN; Domain=apple.com; Path=/; Secure; HttpOnly
        //Pragma: no-cache
        //Expires: Thu, 01 Jan 1970 00:00:00 GMT
        //Cache-Control: no-cache
        //Cache-Control: no-store
        //Location: /auth
        //Set-Cookie: acn01=Zl7DHyBAnYBVeF1Wyg624Hai0x+3PKlEEh3udB5cnAQAGlUI6HE0zQ==; Max-Age=31536000; Expires=Sun, 29 Jun 2025 16:44:41 GMT; Domain=apple.com; Path=/; Secure; HttpOnly
        //X-Apple-HC-Bits: 12
        //X-Apple-HC-Challenge: dc063eb7988fd995d9e52138643aa169
        //X-Apple-ID-Session-Id: 90E43597BF90D2696A23796E3CC61018B24F9EB5AEC0DD2AAEE771D797488FD8BDA7D500043E312708FCA62BFB491914804E34DA3AA46195836B9D2425F73D395961913F46D7D1E34D76E08934E2C998782410ED910E3EAA1F7B60027950F8B7CF6F01A2FC45B955A27D2A3628EC56DBFF67439E0C1E427D
        //X-Apple-Auth-Attributes: Waqim5nZtpDbgqDNovJA5AlUKBjUheU9lCGYRcpkfqaWCZU/OGxfed/0piVlHTg1Ma0GXSQF+1+gvlMdbJatpo35xatLAba69WIWa86bnXvnjIQjzRxouAfL7KyPDYsFtYPxs327IGwrwUrz+jLAKJ37c0vrpjX3IHWFvwB0J6WEsrPTM1U4Wf4mwqO75/D/18lXmCrlTjQ57QAaVQjogKmB
        //X-Apple-ID-Account-Country: HKG
        //Cache-Control: no-store
        //vary: accept-encoding
        //Content-Language: zh-CN-x-lvariant-CHN
        //
        //19
        //{
        //  "authType" : "hsa2"
        //}
        //0

        //401
        //{
        //  "serviceErrors" : [ {
        //    "code" : "-20101",
        //    "message" : "Apple ID 或密码不正确",
        //    "suppressDismissal" : false
        //  } ]
        //}
        //0
    }


    /**
     * 双重认证首页
     * @return Response
     * @throws GuzzleException
     * @throws LockedException
     * @throws UnauthorizedException
     */
    public function auth(): Response
    {
        //GET https://idmsa.apple.com/appleauth/auth HTTP/1.1

        //Accept-Language: zh-CN,zh;q=0.9
        //Cookie: idclient=web;
        // dslang=CN-ZH;
        // site=CHN;
        // aidsp=C8629F8E0EA2C2A34BEF43D5E6394175FC18219695B0D537434183356E621599195BCFC7DEF7AE5423C2761C146D1DA49BDD35E47B5D9A945A5E5CAC65B7462B51112A4900A06A9ED4A3B9616F8655576090FF6950C4AE3560839E092A6C85A109E6D0CD8257DA74350D963EB48F1CA422199465EE58BE59;
        // aasp=A79FA5C9107AC7D03C84480AB286BDCC19372E561B3BA74D8F54A5FB072F07A52C3B67A65452DFFE4EF19F1D7BC01A07362DB7D611CE512859AE63DBDD639CDF1EB3208A653048AAE784CB161C2BF5F248636CE5B87AF6753F66DBA8273B984EA1099D682D7FD1467983CABAA81244DDBF9041000F6BD9B7;
        // acn01=BqZ27U6d1Ue4peh0oQhNrm/+AF81iz1I+FsxRMcAHkuIX9wGWg==

        // dslang=US-EN;
        // site=USA;
        // aasp=6D44FF49F7D00F8D46F7B832845274F0057C40DDA28E744E1D0C8F2B6931AB0BAEE3C04C72780CB68DB540ED330ABFE3F643F9D1F38BA702120459FC8730E96F429F96D7FCF7FC9F823FA4E582F932B09935D7C9623CA1789E0120492B8145C8EFA7FE62FD092BB6E01ECC9C701698A50C83B88DF31572B1;
        // acn01=ygjahyzEhBcdYRVM2WvtlmUy9krc1oadVVeL6AkAHPTG1TgOHg==

        //Referer: https://idmsa.apple.com/
        //User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36
        //scnt: AAAA-kE3OUZBNUM5MTA3QUM3RDAzQzg0NDgwQUIyODZCRENDMTkzNzJFNTYxQjNCQTc0RDhGNTRBNUZCMDcyRjA3QTUyQzNCNjdBNjU0NTJERkZFNEVGMTlGMUQ3QkMwMUEwNzM2MkRCN0Q2MTFDRTUxMjg1OUFFNjNEQkRENjM5Q0RGMUVCMzIwOEE2NTMwNDhBQUU3ODRDQjE2MUMyQkY1RjI0ODYzNkNFNUI4N0FGNjc1M0Y2NkRCQTgyNzNCOTg0RUExMDk5RDY4MkQ3RkQxNDY3OTgzQ0FCQUE4MTI0NEREQkY5MDQxMDAwRjZCRDlCN3wxAAABkKIw2rUpynOF_EeBrorXNCViEg6bYTpZ6Tvms8iwWRNiIcS5iOQb8BD38lN_AB5LiFa-k7QXVd_NSKIZLnWFpGxtaBjM4AB3JgShH2GqdD4nyb3NTg
        //X-Apple-Widget-Key: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Apple-OAuth-Redirect-URI: https://appleid.apple.com
        //X-Apple-OAuth-Client-Id: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Apple-OAuth-Client-Type: firstPartyAuth
        //X-Apple-ID-Session-Id: A79FA5C9107AC7D03C84480AB286BDCC19372E561B3BA74D8F54A5FB072F07A52C3B67A65452DFFE4EF19F1D7BC01A07362DB7D611CE512859AE63DBDD639CDF1EB3208A653048AAE784CB161C2BF5F248636CE5B87AF6753F66DBA8273B984EA1099D682D7FD1467983CABAA81244DDBF9041000F6BD9B7
        //X-Apple-Auth-Attributes: CXfdRQun/XnJ9P7gKBWge72440ufSdLPTe5S0hN+WdxMw+RhtDymQy9U4JETTShnADT5KJgEFoQGz2eP1oOldeMwL8yoSDpwlynyOP+WifD1i9GPysErnSjlijlM6ec8PNZJUAZ+9ynaUXzx9OO6I1vstEOT5e8f1L/pJ9Sro4JyfRRt9GJtIKSpPodMsOPBPTvw3Et8sxZviwAeS4hf6RKF
        //X-Apple-Domain-Id: 1
        //Origin: https://idmsa.apple.com

        //GET https://idmsa.apple.com/appleauth/auth HTTP/1.1
        //Connection: Keep-Alive
        //Accept: */*
        //Accept-Language: zh-CN,zh;q=0.9
        //Cookie: idclient=web; dslang=CN-ZH; site=CHN; aidsp=A615630F6BD1DCFBDB386D5438414FF5BA8FD78888BE9E63DFC673D6BD9D424CDE899BC2E4D841CA936047F7D35189A9F417A36997FFEC2B4DEDD749E2203662E7CBF6460972ABE33AD5E6945B5DEF5F55CD1A7849ECE3C98D41A94CA02AC6D45740C7534ED5FEBF7E27D85E3E292B8B2FD56CA5C16BEAC9; aasp=90E43597BF90D2696A23796E3CC61018B24F9EB5AEC0DD2AAEE771D797488FD8BDA7D500043E312708FCA62BFB491914804E34DA3AA46195836B9D2425F73D395961913F46D7D1E34D76E08934E2C998782410ED910E3EAA1F7B60027950F8B7CF6F01A2FC45B955A27D2A3628EC56DBFF67439E0C1E427D; acn01=Zl7DHyBAnYBVeF1Wyg624Hai0x+3PKlEEh3udB5cnAQAGlUI6HE0zQ==
        //Host: idmsa.apple.com
        //Referer: https://idmsa.apple.com/
        //User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36
        //scnt: AAAA-jkwRTQzNTk3QkY5MEQyNjk2QTIzNzk2RTNDQzYxMDE4QjI0RjlFQjVBRUMwREQyQUFFRTc3MUQ3OTc0ODhGRDhCREE3RDUwMDA0M0UzMTI3MDhGQ0E2MkJGQjQ5MTkxNDgwNEUzNERBM0FBNDYxOTU4MzZCOUQyNDI1RjczRDM5NTk2MTkxM0Y0NkQ3RDFFMzRENzZFMDg5MzRFMkM5OTg3ODI0MTBFRDkxMEUzRUFBMUY3QjYwMDI3OTUwRjhCN0NGNkYwMUEyRkM0NUI5NTVBMjdEMkEzNjI4RUM1NkRCRkY2NzQzOUUwQzFFNDI3RHwxAAABkGTwM2jGyj59uUMIYScqOpI344UW8UIOMM5V0jWnuQtqkyLi5603scL95qhGABpVCN5FlS9iTpRFArfyw3F8i1Ntz6RLjnW23hsBHXlCy_J4btAbcg
        //X-Apple-Widget-Key: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Apple-OAuth-Redirect-URI: https://appleid.apple.com
        //X-Apple-OAuth-Client-Id: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Apple-OAuth-Client-Type: firstPartyAuth
        //x-requested-with: XMLHttpRequest
        //X-Apple-ID-Session-Id: 90E43597BF90D2696A23796E3CC61018B24F9EB5AEC0DD2AAEE771D797488FD8BDA7D500043E312708FCA62BFB491914804E34DA3AA46195836B9D2425F73D395961913F46D7D1E34D76E08934E2C998782410ED910E3EAA1F7B60027950F8B7CF6F01A2FC45B955A27D2A3628EC56DBFF67439E0C1E427D
        //X-Apple-Auth-Attributes: Waqim5nZtpDbgqDNovJA5AlUKBjUheU9lCGYRcpkfqaWCZU/OGxfed/0piVlHTg1Ma0GXSQF+1+gvlMdbJatpo35xatLAba69WIWa86bnXvnjIQjzRxouAfL7KyPDYsFtYPxs327IGwrwUrz+jLAKJ37c0vrpjX3IHWFvwB0J6WEsrPTM1U4Wf4mwqO75/D/18lXmCrlTjQ57QAaVQjogKmB
        //X-Apple-OAuth-Response-Type: code
        //X-Apple-OAuth-Response-Mode: web_message
        //X-Apple-Domain-Id: 1
        //Origin: https://idmsa.apple.com
        //Sec-Fetch-Site: same-origin
        //Sec-Fetch-Mode: cors
        //Sec-Fetch-Dest: empty

        $response = $this->request('GET', '/appleauth/auth', [
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::HEADERS     => [

                'X-Apple-ID-Session-Id'   => $this->user->getHeader('X-Apple-ID-Session-Id') ?? '',
                'X-Apple-Auth-Attributes' => $this->user->getHeader('X-Apple-Auth-Attributes') ?? '',
                'Accept'                      => 'application/json, text/javascript, */*; q=0.01',
                'Content-Type'                => 'application/json',
            ],
        ]);

        if ($response->getStatus() !== 200) {

            session()->flash('Error', $response->getFirstErrorMessage());

//            throw new LockedException($response->getFirstErrorMessage(),$response->getStatus());
        }

        return $response;

        //HTTP/1.1 200
        //Server: Apple
        //Date: Sat, 29 Jun 2024 16:44:42 GMT
        //Content-Type: text/html;charset=UTF-8
        //Transfer-Encoding: chunked
        //Connection: keep-alive
        //X-Apple-I-Request-ID: e21efc39-3636-11ef-a514-f1a4c685053c
        //X-FRAME-OPTIONS: ALLOW-FROM https://appleid.apple.com
        //X-Content-Type-Options: nosniff
        //X-XSS-Protection: 1; mode=block
        //Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
        //Content-Security-Policy: default-src 'self' ; child-src blob: ; connect-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://webcourier.sandbox.push.apple.com https://xp-qa.apple.com ; font-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ; frame-src 'self' https://appleid.apple.com https://gsa.apple.com ; img-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://*.mzstatic.com data: https://*.apple.com ; media-src data: ; object-src 'none' ; script-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ; style-src 'unsafe-inline' 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ;  frame-ancestors 'self' https://appleid.apple.com;
        //Referrer-Policy: origin
        //X-BuildVersion: R12
        //scnt: AAAA-jkwRTQzNTk3QkY5MEQyNjk2QTIzNzk2RTNDQzYxMDE4QjI0RjlFQjVBRUMwREQyQUFFRTc3MUQ3OTc0ODhGRDhCREE3RDUwMDA0M0UzMTI3MDhGQ0E2MkJGQjQ5MTkxNDgwNEUzNERBM0FBNDYxOTU4MzZCOUQyNDI1RjczRDM5NTk2MTkxM0Y0NkQ3RDFFMzRENzZFMDg5MzRFMkM5OTg3ODI0MTBFRDkxMEUzRUFBMUY3QjYwMDI3OTUwRjhCN0NGNkYwMUEyRkM0NUI5NTVBMjdEMkEzNjI4RUM1NkRCRkY2NzQzOUUwQzFFNDI3RHwyAAABkGTwNNiM4ZQxQJrf-h2bQuHPfQ0m8go502ZI6PDNkQIRYNvqtQziulQTume5ABpVCpTZ54RZQ3hhA7SifzCO4qeS8Qi6lPN_yiujATemLmCBehw2Rw
        //Set-Cookie: dslang=CN-ZH; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: site=CHN; Domain=apple.com; Path=/; Secure; HttpOnly
        //Pragma: no-cache
        //Expires: Thu, 01 Jan 1970 00:00:00 GMT
        //Cache-Control: no-cache
        //X-Apple-AK-Auth-Type: hsa2
        //Set-Cookie: dslang=CN-ZH; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: site=CHN; Domain=apple.com; Path=/; Secure; HttpOnly
        //Cache-Control: no-store
        //Set-Cookie: crsc=RQfo43d1tX54YgGoQL43n5+dVYOmc9Z4rk3hwngArr+g867XTV9suAnA1KwYhXrtrD3wc5R5TmqyFLO5oQAaVQq1lmO1; Max-Age=900000; Expires=Wed, 10 Jul 2024 02:44:42 GMT; Domain=idmsa.apple.com; Path=/; Secure; HttpOnly
        //X-Apple-ID-Session-Id: 90E43597BF90D2696A23796E3CC61018B24F9EB5AEC0DD2AAEE771D797488FD8BDA7D500043E312708FCA62BFB491914804E34DA3AA46195836B9D2425F73D395961913F46D7D1E34D76E08934E2C998782410ED910E3EAA1F7B60027950F8B7CF6F01A2FC45B955A27D2A3628EC56DBFF67439E0C1E427D
        //X-Apple-Auth-Attributes: u7ZbEJN9JbSdGNwZgwynW/aMatDUeCQrM5h1fVh7mq+BbpV1/w3w9kJCdhZbhNDVhP8VVzDN/V0WTlP81/eVmHVHht2Tj+QJ32IGviAd8+bKjn+SEYwvrsnT2Wi0URMtvuN0u6sB5K77YKVU+DdAQz8EP8yBo0VGCX9oogOXMkjsx0KT5/XuAguTwNWKNwGEC3Tg0HUi0Lh0QgAaVQq1pGoG
        //X-Apple-ID-Account-Country: HKG
        //X-Apple-I-Rscd: 201
        //vary: accept-encoding
        //Content-Language: zh-CN-x-lvariant-CHN
        //
        //34d8

        //method":"GET","uri":"https://idmsa.apple.com/appleauth/auth",
        //"headers":{"X-Apple-Widget-Key":["af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3"],
        //"X-Apple-OAuth-Redirect-URI":["https://appleid.apple.com"],
        //"X-Apple-OAuth-Client-Id":["af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3"],
        //"X-Apple-OAuth-Client-Type":["firstPartyAuth"],"x-requested-with":["XMLHttpRequest"],
        //"X-Apple-OAuth-Response-Mode":["web_message"],
        //"X-APPLE-HC":["1:12:20240626165907:82794b5d498b7d7dc29740b23971ded5::4824"],
        //"X-Apple-Domain-Id":["1"],"Origin":["https://idmsa.apple.com/appleauth"],
        //"Referer":["https://idmsa.apple.com/appleauth"],
        //"Accept":["application/json, text/javascript, */*; q=0.01"],"User-Agent":["Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36"],
        //"Content-Type":["application/json"],"Priority":["u=1, i"],
        //"Sec-Ch-Ua":["Chromium;v=124, Google Chrome;v=124"],
        //"Sec-Ch-Ua-Mobile":["?0"],"Sec-Ch-Ua-Platform":["Windows"],
        //"Sec-Fetch-Dest":["empty"],"Sec-Fetch-Mode":["cors"],
        //"Sec-Fetch-Site":["same-origin"],
        //"Host":["idmsa.apple.com"],
        //"scnt":["AAAA-jU3NUREM0RFQjA3NUFGNzhBMjhBRDhDQjdENTA3RkMzQTY1NzAwODc4NjM2MUY2QzEwOTVGQUE4NkMwQzlFQTcwQjM0MUQ1NzA2MDlCMjBERkFGRUI3RkQ2REJBNzdCOTgyQzg1NDNERjYwODNBNjFBMjgxQkVFOUEwMDU0NEFCOEZFNkRGRUFGQkZCMDNCNkRDNTU1NTFENjI3MUExMUVBOTJBNjIyQ0Q3NkFDQjg1MUM2RUU5OTgzMzNGRjZBNUFENTI0MzRDRTA2RDVBRTFGRjdCNzEzOUQwNzk0MUI4OUYyMjI5MjY3NzJBOTJCOXwxAAABkKzLnH77qJfJe_6iYD7v8cOJRuoldV_7fWHF11vo9tyn7vHyULSYsUxoLMPBAB707DwdM6y0o-SbxZjhAqyDWtuErUeIEmB_DZYEp9lsXkncyGtV9A"],
        //"X-Apple-ID-Session-Id":["575DD3DEB075AF78A28AD8CB7D507FC3A657008786361F6C1095FAA86C0C9EA70B341D570609B20DFAFEB7FD6DBA77B982C8543DF6083A61A281BEE9A00544AB8FE6DFEAFBFB03B6DC55551D6271A11EA92A622CD76ACB851C6EE998333FF6A5AD52434CE06D5AE1FF7B7139D07941B89F222926772A92B9"],
        //"Cookie":["dslang=US-EN; site=USA;
        // aasp=575DD3DEB075AF78A28AD8CB7D507FC3A657008786361F6C1095FAA86C0C9EA70B341D570609B20DFAFEB7FD6DBA77B982C8543DF6083A61A281BEE9A00544AB8FE6DFEAFBFB03B6DC55551D6271A11EA92A622CD76ACB851C6EE998333FF6A5AD52434CE06D5AE1FF7B7139D07941B89F222926772A92B9;
        // acn01=SYnZh8XNGzQ1ESpwUvdULC9fljOMPyEly62/gGUAHvTsRMj8gg=="]
    }


    /**
     * 验证手机验证码（登录）
     * @param int $id
     * @param string $code
     * @return Response
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function validatePhoneSecurityCode(string $code, int $id = 1): Response
    {
        //POST https://idmsa.apple.com/appleauth/auth/verify/phone/securitycode HTTP/1.1
        //Connection: Keep-Alive
        //Content-Type: application/json; Charset=UTF-8
        //Accept: application/json
        //Accept-Language: zh-CN,zh;q=0.9
        //Cookie: idclient=web; dslang=CN-ZH; site=CHN;
        // aidsp=0562F50FE3CE81366007E772369D847191759222C8851F85F842B48BB6323D128458D33CFF50F28EAF21502DDE47109F5D186973240EC682743F7EC07B58FF37F23F46271BF3DD82DDDE8FE2593B3F41524AB77ECEDC62E50466EB1732BDA349CCF8835FF15126030CEEE03E82F36A42BEA84366555DF018;
        // aasp=F80190F4FD80B689D2B92B383AC4B8C1EF55795E9C47C39C777D7E1AEC4452F12A219F6F540379F6A361BE1685C494DE38C6973A08A755B3EDA3F1326F3718C2B1C78BBFE5A455EA9D41C556D182961CC385907710EF70A3E8CEC61CA509CA668750EF60BD570D42021E94AA1A41BE2F5F3149E1C1B3AFAA;
        // acn01=ZcT6K8g6hiycDKk/DzoSUlmT+dj134Hmy4OkJeCr9wA1QC8/Tjvk;
        // crsc=5mF3IesG/Zg+35inKMSKCOCHMCBZC70Zqkb4L+29BfOBaq9111U54/PblKyDwNfG6z71t7z3F39WuVvKT6YANUAgVeQ90Q==

        //Host: idmsa.apple.com
        //Referer: https://idmsa.apple.com/
        //User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36
        //scnt: AAAA-kY4MDE5MEY0RkQ4MEI2ODlEMkI5MkIzODNBQzRCOEMxRUY1NTc5NUU5QzQ3QzM5Qzc3N0Q3RTFBRUM0NDUyRjEyQTIxOUY2RjU0MDM3OUY2QTM2MUJFMTY4NUM0OTRERTM4QzY5NzNBMDhBNzU1QjNFREEzRjEzMjZGMzcxOEMyQjFDNzhCQkZFNUE0NTVFQTlENDFDNTU2RDE4Mjk2MUNDMzg1OTA3NzEwRUY3MEEzRThDRUM2MUNBNTA5Q0E2Njg3NTBFRjYwQkQ1NzBENDIwMjFFOTRBQTFBNDFCRTJGNUYzMTQ5RTFDMUIzQUZBQXwxAAABkGjgCfKJK6nuKKmEwMjCJLiF4jgHS4k5iQkYb00IpWDb2V4JxZga1PHIQBCrADVALzRCMekvs-St0xxzBHzLCU_FvSnqCX5sYKQbGcYeTiE9ZOU-Ig
        //X-Apple-Widget-Key: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Apple-OAuth-Redirect-URI: https://appleid.apple.com
        //X-Apple-OAuth-Client-Id: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Apple-OAuth-Client-Type: firstPartyAuth
        //x-requested-with: XMLHttpRequest
        //X-Apple-ID-Session-Id: F80190F4FD80B689D2B92B383AC4B8C1EF55795E9C47C39C777D7E1AEC4452F12A219F6F540379F6A361BE1685C494DE38C6973A08A755B3EDA3F1326F3718C2B1C78BBFE5A455EA9D41C556D182961CC385907710EF70A3E8CEC61CA509CA668750EF60BD570D42021E94AA1A41BE2F5F3149E1C1B3AFAA
        //X-Apple-Auth-Attributes: bex4PtjbDWOoYH69eH2XSKl03CT8m7fVrsuj+bQ9ZebT+jVU/qh0fx70vcKTIylqU+R41uKrpPZd617bE0kgeENgoIxv9JChJuHAjUwSgE6tInRcH3XXkNrytZeUFhMGuHEtiqRe8H1FXWhuPnvXO0Z3ws00vVNHTTdY2xeQqlebmzex1EZKIoW8OTunIOagLDlYNjHAh9TQwAA1QC8/Wyom
        //X-Apple-OAuth-Response-Type: code
        //X-Apple-OAuth-Response-Mode: web_message
        //X-Apple-Domain-Id: 1
        //Origin: https://idmsa.apple.com
        //Sec-Fetch-Site: same-origin
        //Sec-Fetch-Mode: cors
        //Sec-Fetch-Dest: empty
        //Content-Length: 70
        //
        //{"phoneNumber":{"id":1},"securityCode":{"code":"056011"},"mode":"sms"}

        //{"phoneNumber":{"id":1},"mode":"sms"}

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
        $cookies = $this->cookieJar->getIterator();

        return $response;

        //HTTP/1.1 200
        //Server: Apple
        //Date: Sun, 30 Jun 2024 11:08:47 GMT
        //Content-Type: application/json;charset=UTF-8
        //Transfer-Encoding: chunked
        //Connection: keep-alive
        //X-Apple-I-Request-ID: 1f09dec0-36d1-11ef-8fd4-29ae4f8ea18c
        //X-FRAME-OPTIONS: DENY
        //X-Content-Type-Options: nosniff
        //X-XSS-Protection: 1; mode=block
        //Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
        //Content-Security-Policy: default-src 'self' ; child-src blob: ; connect-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://webcourier.sandbox.push.apple.com https://xp-qa.apple.com ; font-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ; frame-src 'self' https://appleid.apple.com https://gsa.apple.com ; img-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://*.mzstatic.com data: https://*.apple.com ; media-src data: ; object-src 'none' ; script-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ; style-src 'unsafe-inline' 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ;
        //Referrer-Policy: origin
        //X-BuildVersion: R12
        //scnt: AAAA-kY4MDE5MEY0RkQ4MEI2ODlEMkI5MkIzODNBQzRCOEMxRUY1NTc5NUU5QzQ3QzM5Qzc3N0Q3RTFBRUM0NDUyRjEyQTIxOUY2RjU0MDM3OUY2QTM2MUJFMTY4NUM0OTRERTM4QzY5NzNBMDhBNzU1QjNFREEzRjEzMjZGMzcxOEMyQjFDNzhCQkZFNUE0NTVFQTlENDFDNTU2RDE4Mjk2MUNDMzg1OTA3NzEwRUY3MEEzRThDRUM2MUNBNTA5Q0E2Njg3NTBFRjYwQkQ1NzBENDIwMjFFOTRBQTFBNDFCRTJGNUYzMTQ5RTFDMUIzQUZBQXw0AAABkGjjBTQkpOySP-0kVkdDFDsYT__5neb9ILIW_Cpdp_gEqgAKOEXfSwPSsSf1ABqgy2qjwaaHIUxnUAeGELOswABULwRatMjv5TDU0_iErlilULV_PQ
        //Set-Cookie: dslang=CN-ZH; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: site=CHN; Domain=apple.com; Path=/; Secure; HttpOnly
        //Pragma: no-cache
        //Expires: Thu, 01 Jan 1970 00:00:00 GMT
        //Cache-Control: no-cache
        //Cache-Control: no-store
        //Set-Cookie: crsc=; Max-Age=0; Expires=Thu, 01 Jan 1970 00:00:10 GMT; Domain=idmsa.apple.com; Path=/; Secure; HttpOnly
        //X-Apple-ID-Session-Id: F80190F4FD80B689D2B92B383AC4B8C1EF55795E9C47C39C777D7E1AEC4452F12A219F6F540379F6A361BE1685C494DE38C6973A08A755B3EDA3F1326F3718C2B1C78BBFE5A455EA9D41C556D182961CC385907710EF70A3E8CEC61CA509CA668750EF60BD570D42021E94AA1A41BE2F5F3149E1C1B3AFAA
        //X-Apple-Auth-Attributes: S+sP/y5OLJa+dfL+b6pbBWzI+38sPBD4aInb2CXke+gn9wXBajFa7ZpPdPAbbYF+o6e7Z+QhlxBiZmzGAlWzLZo5H+WFBsYDrF6zEw7WYMYbM6PrC4lVxr7gHdlooNKkY7SCBPtfelD8KMMWfFIlUKx81N9jqfeGOOKSN+bFTS38oxURb7NHqD8xa8EEjIMJzPu0sHyxJcnTAgAaoMuQk4YM
        //Set-Cookie: myacinfo=DAWTKNV323952cf8084a204fb20ab2508441a07d02d356c9a9e48916520085c3cbc21c2532dfc92ea37f2d8ede62a39be232133c5b17365830bdd1d11fc83bdd5a077cd024d0195452d5b7ba7f82243d75b919028cbc500bc0bccdb8fe9a8ee5b548574bb8919822ecb359d2a8fed7bdd02f967b8edd17f48fbe2bf5326132af77fdb6a476db697aac8db8f32475f527d4340d326dca8bf65d54160ae2772374477146e989ad7d142166206ef25ae7da769621a574370f423021a807beebd2bbeea26471bf426bd3b64165a56fb77df9aa7bfb2082a541a43463d7d03097858f3a9cbba9705b7a10dadd57522ba6b0ff08ad4eba9ba5b58dc78b096c4f42fd8221b970a3ec848008da4e07f20a83483c7170657e121e9c4d24bca18cb6c54eb824c1e81d87b97d4f036aed61325476228026681244c319b05660d4e356f2a9d393763882b27a8524f28b0919ddd7416844f1bb795a74bac9c10ec1f7204ad2d92e0b74c07c5ded651dcc79b94b8b85db8c5497503c70245ea10a1e782572fcef7778ba8d4e7e293edfae2102b192d4fc099aa5510757200249cabfc4e0ea486972abfd8ee335a0190503148681e11e0037000c12b5194ca4b371fa4507ce9ca113a67d36ebf68ec81d01cd0035ec3963746ee9b90a31ab3716bc89d3df848d91be34cd9815260a4e1a8b4ee0f3df462e99f02e66657153b9ea5a8fe8acbb6fa7adb6e7a02e12cbb570a9b3975358e6fa343012d644b6d9452ea025b2e5d416171c5caf395e895b172c3c963f0542a1d21c952d48fc7d165250d84147083997c6b9de74a53b681ad2d963132e2699782e4a9b439193a61aeb695b45d1e350b2b6726efe8c488e10cfec4fd5b8c011ef5d5e0fc35cb4c54632741b5ada51c1fefa108e88c4b3fb5e28b0077ac075653269887fd337f4fc089c4a787c77aae122ebbcf849807df2e92244fc5333d8c74f56b8ca9b56403a7004bd4480ad17dfa2268a811b2fda3f585a47V3; Domain=apple.com; Path=/; Secure; HttpOnly
        //X-Apple-ID-Account-Country: CHN
        //Cache-Control: no-store
        //vary: accept-encoding
        //Content-Language: zh-CN-x-lvariant-CHN
        //
        //78f
//        {
//          "trustedPhoneNumbers" : [ {
//            "numberWithDialCode" : "+86 ••• •••• ••70",
//            "pushMode" : "sms",
//            "obfuscatedNumber" : "••• •••• ••70",
//            "lastTwoDigits" : "70",
//            "id" : 1
//          } ],
//          "phoneNumber" : {
//            "numberWithDialCode" : "+86 177 5246 3370",
//            "pushMode" : "sms",
//            "obfuscatedNumber" : "•••••••••70",
//            "lastTwoDigits" : "70",
//            "id" : 1
//          },
//          "securityCode" : {
//            "code" : "056901",
//            "tooManyCodesSent" : false,
//            "tooManyCodesValidated" : false,
//            "securityCodeLocked" : false,
//            "securityCodeCooldown" : false,
//            "valid" : true
//          },
//          "mode" : "sms",
//          "type" : "verification",
//          "authenticationType" : "hsa2",
//          "recoveryUrl" : "https://iforgot.apple.com/phone/add?prs_account_nm=judy7024895%40hotmail.com&autoSubmitAccount=true&appId=142",
//          "cantUsePhoneNumberUrl" : "https://iforgot.apple.com/iforgot/phone/add?context=cantuse&prs_account_nm=judy7024895%40hotmail.com&autoSubmitAccount=true&appId=142",
//          "recoveryWebUrl" : "https://iforgot.apple.com/password/verify/appleid?prs_account_nm=judy7024895%40hotmail.com&autoSubmitAccount=true&appId=142",
//          "repairPhoneNumberUrl" : "https://gsa.apple.com/appleid/account/manage/repair/verify/phone",
//          "repairPhoneNumberWebUrl" : "https://appleid.apple.com/widget/account/repair?#!repair",
//          "aboutTwoFactorAuthenticationUrl" : "https://support.apple.com/kb/HT204921",
//          "autoVerified" : false,
//          "showAutoVerificationUI" : false,
//          "supportsCustodianRecovery" : false,
//          "hideSendSMSCodeOption" : false,
//          "supervisedChangePasswordFlow" : false,
//          "trustedPhoneNumber" : {
//            "numberWithDialCode" : "+86 ••• •••• ••70",
//            "pushMode" : "sms",
//            "obfuscatedNumber" : "••• •••• ••70",
//            "lastTwoDigits" : "70",
//            "id" : 1
//          },
//          "hsa2Account" : true,
//          "restrictedAccount" : false,
//          "supportsRecovery" : true,
//          "managedAccount" : false
//        }
        //0
    }


    /**
     * 获取token
     * @return Response
     * @throws GuzzleException
     */
    public function accountManageToken(): Response
    {
        //Cookie: idclient=web; dslang=US-EN; site=USA;
        // aidsp=F91CA472B7E5593F5484D837C2D42C25BD69850B5A1C6A638B8A59BD6E6D7C6DDC5D6B44091D2E1421FB470EE917159CB59A9DE7A73E1C4821C14B7BD986A6F6891D80755D5268D447A57693F635D2A7FE5FBBBC3D1A34BB007B098E74717A8D36112A3B1096E99B4B9BE38447786981A0C95FF382EEA11D;
        // acn01=uxT1cXrmg7lEq/xgEAI+ACfbUVpMagy0pJ7udioAHsgB44FM1A==;
        // myacinfo=DAWTKNV323952cf8084a204fb20ab2508441a07d02d33ece397305946da9821057d39e52c6985f6dcf3365cc1a87d39820f56a8bc0e0c823c0328368ae1f5131269d1e4015cd0d202d82c5567d6e21b2f7fd3dbe71dd004e7c6224b07fe58e925a910386911528caa9c3bfa4e0997b2e858dade9f0e2812e8db8c5c1e66a47611a72acac39cf15a3e76194829abcd70e6feb13b6a1770bdcab21b80e050bac53d47e457f91adf65e8026764941bb91c049622fe5be162936dc333304cb0c877c82535be7571209d448aa1058f6a6fdef6bf945c1ec1183ebe4eee3f88e2bb6041306b8d74734f41c15ff4d2f3149d3660f35fe8881ab38fdd00841fa4d842c2ce891ad41e007e95839864684440d8d7f61996d84b8d425a66562e36ed5ffe70a75e640630cd040608118ba11cc7a195fb434cd199d2c5d53fd998727a7f3d0c7752087558ca137e30ff04b839439b2174ef9ce9630b77ebfa19a46ed94c521406bd15f9550f8a09d3017c91ff998206c5f610de558c8c48737b7002cdde510f0ba64f6b697ce4ba5c76a36fe5929aeee5475d93917ac7d0c2a572cf21d754cb93e3e4f08880d57d20d85c7c6cc64ef45d36b309268c86d19ed735841338a88511b28d214e380ebdfa76d7af02685a9d1dcd9752eac6bb1d07cc1f19497e5ea067a4db2ca72a9fbc1d4334e7820a0f73c9cb486c88f1bf467dd61649087c7cbcf02e3f721351532012cce4bc5034350a00c812da0a58c0168817a09bfff9fede93ee386bb9589c5044c680dcd229ddd2a0364ffc208e3282ba0a81e2f38499b68ed429a521a24ff30621f6333696dda9affe7e1193f3bcf6af6f93e9132ea1557fb9875a784d4217b6b035c6d2e5d173d13da56c20963585a47V3

        //GET /account/manage/gs/ws/token HTTP/1.1
        //Accept: application/json, text/plain, */*
        //Accept-Encoding: gzip, deflate, br, zstd
        //Accept-Language: en,zh-CN;q=0.9,zh;q=0.8
        //Connection: keep-alive
        //Content-Type: application/json
        //Cookie: dslang=US-EN; site=USA; geo=CN; idclient=web;
        // aidsp=63E8E7B44B12C4A67EC1A6FDBA9421DDF10053BA55C31579DF25C193447FA63A23A6781F4FE8F026E733DE857B00BE8651E75554E42DBC4E1CC6AE1A9E4A1CE9ACF95BB4FF198F0DD30DF29E125C7D5A0FA39788031376E4CC3C8F8F2B8FCC34858E9E05EE6098A942F22C4A7BAA49500274DAEE7E4A0ECC;
        // aid=F662049FE507BDE9CB1B8BEF2BA5CD7B;
        // myacinfo=DAWTKNV323952cf8084a204fb20ab2508441a07d02d3ee6d618cbdf73fd024765af5069a467fce19dfeb105397d3a0688a1df498def94824fcfab32307ec51adc51d4d6286885b9582e54f26a475829358b0bfb75db32fe2d0f013edd3410c7bfcd3c096b3c38a8ce43980c5b789829fd79eb056df76067bed8324fd9545f9c4cf7d20d899eed0e93e502219eca313f02562728eb338856935fa52f53efdf4461cd07400d5e1345df34dedb51015c35c424bf760697cc08bc847174b26c39ae9ccff1928f206b9c82e824b4fb428ae914852fac67d3e0d707e3a8324da9b935dd6b3f5606b353e7465ea3ed2baaada60dadb22da1d7768d4635272c61d9ac519dbd0d3deeada8146d60317d767192ae98a6a12e3dff345cbf31bfc31934871f3537191a26e1c61d06ed17fbbe7a7c93778ccf4ee3d1ebf0cd226444548928dae5884565a6b9a9068f5633bcd895b0c88d7b9cf2af35b3020bde6aea20dafc1e8dd2c4f770da9be1963c1e5ab6ae2ff8e2c0a406c121970a5a0f383cd35d251199a07c6dc6da5498fc5cf0a35fc2f25551f3d63b91be0699a39e0b4cdbf6ccc0ba0d56d7d4541d1bb4dde67e9d5d147ca252d082c5b3e49ebd51a83439306488a71e7951f71c738517ed9fced73473f51e08d345c3429117647992f449bba91efb891238b83ccc112509e7bbef4defae8e1cbb3287237fc7a63ff5dbd50076423b5c02ff1d3e5f90eb9f4242bb924a590149467b292ac8c3ff594799e7645d5392e39e71c841de8ec1d90325eb20f282b61e88ad0428ea06641dbb838c2bf9cb200cf2ba733926fb6a8c72b52f50f23cba9126cf224ef11e52a8eab1b2555658ee4700ad19aa22178498da2dc552afee45466ea9a4fab585a47V3
        //Host: appleid.apple.com
        //Referer: https://appleid.apple.com/
        //Sec-Fetch-Dest: empty
        //Sec-Fetch-Mode: cors
        //Sec-Fetch-Site: same-origin
        //User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36
        //X-Apple-I-FD-Client-Info: {"U":"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36","L":"en","Z":"GMT+08:00","V":"1.1","F":"7la44j1e3NlY5BNlY5BSmHACVZXnNA9e6gZ6F5L5LzLu_dYV6Hycfx9MsFY5Bhw.Tf5.EKWJ9VbHb42tJjn9UkW5BNlY5CGWY5BOgkLT0XxU..0jp"}
        //X-Apple-I-Request-Context: ca
        //X-Apple-I-TimeZone: Asia/Shanghai
        //scnt: AAAA-jYzRThFN0I0NEIxMkM0QTY3RUMxQTZGREJBOTQyMURERjEwMDUzQkE1NUMzMTU3OURGMjVDMTkzNDQ3RkE2M0EyM0E2NzgxRjRGRThGMDI2RTczM0RFODU3QjAwQkU4NjUxRTc1NTU0RTQyREJDNEUxQ0M2QUUxQTlFNEExQ0U5QUNGOTVCQjRGRjE5OEYwREQzMERGMjlFMTI1QzdENUEwRkEzOTc4ODAzMTM3NkU0Q0MzQzhGOEYyQjhGQ0MzNDg1OEU5RTA1RUU2MDk4QTk0MkYyMkM0QTdCQUE0OTUwMDI3NERBRUU3RTRBMEVDQ3wzAAABkK-5xzXRDaEPxb4n7u0gFb6B4hbi-aVvMATb3o5oEQSxQmxHhfGhI669Z3j8AB7KMjnG1LKtCBwvWdbbthhWalOEr2CVo5Z3VyZbpZTmq_lMbAMVKw
        //sec-ch-ua: "Not/A)Brand";v="8", "Chromium";v="126", "Google Chrome";v="126"
        //sec-ch-ua-mobile: ?0
        //sec-ch-ua-platform: "Windows"

        //cookie: idclient=web; dslang=CN-ZH; site=CHN; geo=CN;
        // pldfltcid=c30f6120e0484dc6b71e6e784a61e337012;
        // pltvcid=undefined;
        // aidsp=24E040EB9C3B52C3BA31B3F10B6DD2CA9CD1AD68E309CC6E6660A3FB7D8FE6B7F529AD9167F6E2C98B183BB5C95BFDBE5E49508EBD663B239F2B2AFA474E70DE11119DB014CDC9BADA5230C62D22A3E1FCB716B84F747B3DB155B66BFB858A67E58560E2842055EA703C2AEB513A6C178E42D87FE22E3677;
        // aid=9B7BB47F0B3ECBCC8D03D7F79B4F13DA;
        // myacinfo=DAWTKNV323952cf8084a204fb20ab2508441a07d02d33c2ae4095c15fae071c98d5cc340da5f2afca3f9d5b6379dfca5195d952736c745ec1661f2b605a5695dab2f8f508151db8255999dc90c6792ba962e6be419e27d3be6ec899ca39ad57e8c3f3b8bc45d08e2bb1b1e6f7b15536188fb86265c460cf521654d97ba81b552a9f6ef3f8fbf07304e4cbf39b9dbe0535e2b57c07bca7d186b9c205bda6ba5f0a34af08f82a9e3fdc68df5cd4878523f0c8c17f29a365a6828823254564a1e9f4689dd4a8c665b96fb02be69cdf558b4e95cfa6cac4d3eadf8618f16d5672df2a7d5fcaa4b45b163501e1718d78ab5e3fabefdd8288039469b4ef790b933bdca12a0b33f0cbd8b149b72b80e7b61d5c2bedaedb5f95594ca3e763f3f8ad2bd705f3a8762f63d7efc5f631a61a938fba33c99eb9bc0d60cd4fdcd19567f26c3cdd8cab3711597f1e077027101def6d4158373641ca1efd2df3f3cb1c21ce9aa712ac5dd4cd3f3e717b0289606095f605fbb8a293fb1359a66d05fa0ab85caa424c773c9466a0646821f7ec1b71b8e76fb9ba8641c4aa38cda0528e4a503164f25c3dd8ae365cf2a1e882a5255ea7a5cd89a0adc3a4749adbfdc1986fddb984ddfef0a28c78c133172a336cc3a8a017b54a2d0413179a055a9db99f18c57b62d592e87ad92076a14482d1ae8f76a1e6688083d3e99d95726efc850bd7b9e7eab60c02363ae1f2061f4cd6f6b26fe9b1cd3afdd75fe613a43bc5a5ca198f68ae635437adf4bf517f4f5627bd67a9462373ced6f976ce602e91c6dcd9198d2ea19cdf68c1f9707a637758f6aea33df59f76ef6047707fbf24ad60211def8609b489011231b2ed3d0fa001a12778dd083d4c3f6da69291543585a47V3
        //GET https://appleid.apple.com/account/manage/gs/ws/token HTTP/1.1
        //Connection: Keep-Alive
        //Content-Type: application/json
        //Accept: application/json, text/plain, */*
        //Accept-Language: zh-CN,zh;q=0.9
        //Cookie: idclient=web; dslang=CN-ZH; site=CHN;
        // aidsp=552883BF0800E1723057DB2A2EF74188CC835E84F27BDEE37CA08813A3ED9E790268639B08C4AA25589B005EA0330AE7C01CED5412C0CF91BE0BB9AE26FBF5412C63A56346692CBB1ECDB8E454BF545A9F833F9379DFEBCA9F8D09C8B88FE3D48E8F9564A484F98353B195498877B68D3141C019337D4A8C;
        // aasp=6724BFB67CC438D7CBC4F21290C7BDB79A540B2470FF97EED238FE3EF5CF2F9F6BA5FFE21DD1728AFE0E8FAC79B6F841AC643183E31A67C5FED77AB1C4DA0BFBCA9C818CBDBD58FC304C6D5AB6B71583EECFCA4A14FD23C9E7A6BAB659AE510300EEF695B4C576CFC36946C92251A0665CBDC3662E5402A4;
        // acn01=sgpTFD0UXYy03wl6z3NsfH9yGWnPA4E7I3tusCkAGV8FYClc2Q==;
        // myacinfo=DAWTKNV323952cf8084a204fb20ab2508441a07d02d3495f1538cf7dbbcc70a6f042965159d6f9934632b802f1430e3faa0032c41df9bed1718fc78968f6d4d2a08939df332846b8619c3a30597a55476076da51d916bc8c2e6c36bcb74de6fcbab0764eb7faf733e98c21ce6c975f7c324786aa38f03a7a4c8348dea763a0b3c0e8aff80ec18dc214b149faca8445aa74ee0f6c4e94be8dda6d12a6691cd6cc8080b6b350b59c48a847ef4defec286898c3bcfe1c65e4af0dbfdd6c1ca6c624c16fc4483121c5c8ff264fce6208b743ecbaf054119bf0b331919da10b9968a40d32e3172e66f6c98d72ff99eab78b8b4e3c64ef20286943ce64d406c311aa3d2fc75c558f0e686c4fe4247f013741a184eeafd6d23720439d91788b53db8f3a52795358329c33ceb6df6500efe102d3ac25f6902a936662e8466ae4e641906baeab8d3c513b2b7b9be4f57b2d3e33d1669b6056d44e414e242d2a7bc51f18674fecebd366357291f7e8a9bd9e7f0fb7019b1582c52ec55dec4938e16df04a8578ac26de5330625876d73fa6305642e84f691155fb7fb33eb38aa7324dcc0cbcfcf0eb2d66a748f97f959b2e28437ea387f8a2a53bac20f31a9830733534f9d0d624e07ac605966b4ecb715a343bd6055a783b02fbf0bb9d1c944b423cb371c86296a3c47b52a701a71a3b1b0a85b4f53463d1b9b438bd8ab3bfa7824ab00f1bfcebab6ec963f15da02c70b31046a4c0f8c46a9a6807dfd46ff491bba79d39756a62d253213e22d0315c871ed9dd678ea81968be57a1a3116d20edb7a90fceec50ba2801575cbcfa2b693009b94e5623d51916992709bc29e9ab2b9e88cd386435a3a86b90b77376f626d45977d322e7bf08173e93d1585a47V3
        //Host: appleid.apple.com
        //Referer: https://appleid.apple.com/
        //User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36
        //scnt: AAAA-jY3MjRCRkI2N0NDNDM4RDdDQkM0RjIxMjkwQzdCREI3OUE1NDBCMjQ3MEZGOTdFRUQyMzhGRTNFRjVDRjJGOUY2QkE1RkZFMjFERDE3MjhBRkUwRThGQUM3OUI2Rjg0MUFDNjQzMTgzRTMxQTY3QzVGRUQ3N0FCMUM0REEwQkZCQ0E5QzgxOENCREJENThGQzMwNEM2RDVBQjZCNzE1ODNFRUNGQ0E0QTE0RkQyM0M5RTdBNkJBQjY1OUFFNTEwMzAwRUVGNjk1QjRDNTc2Q0ZDMzY5NDZDOTIyNTFBMDY2NUNCREMzNjYyRTU0MDJBNHwxAAABkLA9tNm5V0TX32hVz6kjdgfX182GtZuCgvGDe0z-sGB1DJs1CdMv2fiLlloTABlfBVX5kR1wPoJdGBcN_0WzsvSq235y_evxTukExVaZDHFGvl8AKg
        //X-Apple-I-Request-Context: ca
        //X-Apple-I-TimeZone: Asia/Shanghai
        //Sec-Fetch-Site: same-origin
        //Sec-Fetch-Mode: cors
        //Sec-Fetch-Dest: empty

        return $this->appleidRequest('get', '/account/manage/gs/ws/token', [
            RequestOptions::HEADERS => [
            ],
        ]);

        //HTTP/1.1 200
        //Server: Apple
        //Date: Sun, 30 Jun 2024 11:08:48 GMT
        //Content-Length: 0
        //Connection: keep-alive
        //X-Apple-I-Request-ID: 20188fd8-36d1-11ef-972e-57954f30ec09
        //Set-Cookie: idclient=web; Domain=appleid.apple.com; Path=/; Secure; HttpOnly
        //X-BuildVersion: R12_1
        //X-FRAME-OPTIONS: DENY
        //X-Content-Type-Options: nosniff
        //X-XSS-Protection: 1; mode=block
        //Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
        //Content-Security-Policy: default-src 'self' ; connect-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com https://api.apple-cloudkit.com https://feedbackws.apple-cloudkit.com https://*.icloud-content.com https://*.icloud-content.com.cn ; font-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com ; frame-src 'self' https://idmsa.apple.com https://signin.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://familyws.icloud.apple.com  https://apps.apple.com ; img-src 'self' https://www.apple.com https://appleid.cdn-apple.com data: https://*.mzstatic.com https://appleid.apple.com https://*.icloud.com ; media-src data: ; object-src 'none' ; script-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://signin.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://appleid.apple.com ; style-src 'unsafe-inline' 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com ;
        //Referrer-Policy: origin
        //scnt: AAAA-jk2NUNGODA4NDNBOENDNjVGQ0EzQzc3MzA5OTdBQzg3NDFBODdFODEyNUVFQTZGMzhGRDNFRTE4NEY3RTBGODA1N0JEMERBRTlBODdGMDdGMDM5QjM2MDBCQkFGREU4MTVCQjA4RjM0RkNDQzBGNkZFQTFERDc2MjBFN0EwODg4REI4Nzk2MTBFODMwQzUyNTc5NEZCQzRBQ0VCRTE0Q0FCMjBCNDVDMDUxM0FERkU1OUUxNjYxMkM1QkMyQUM5M0U5Qjg5Mjc5MTQ4RjQ2Q0U0QTQzQjI5RkE5NTk2RjMxOUZGODFFRUE5MDE0REE3NnwyAAABkGjjNEU9qvzt34qBqyUcm12cKxpIPW5lGcM-hCCv4gz0EBPCVGQNTopxZaM5ABqY7Yynh0-WHjMgUcAW0WQTia9DxzP5QGilmN8NHbOncX_28awVMQ
        //Set-Cookie: dslang=CN-ZH; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: site=CHN; Domain=apple.com; Path=/; Secure; HttpOnly
        //Pragma: no-cache
        //Expires: Thu, 01 Jan 1970 00:00:00 GMT
        //Cache-Control: no-cache
        //Cache-Control: no-store
        //Set-Cookie: awat=TURBd05UQTJMVEE0TFRGbE1Ua3lNVEU0TFRjM05XUXROREE0TlMwNE9UazFMVEJoWVRObU5XWmxaVGd5TVRveE56RTVOelExTnpJNE9URTJPakU9OlJrQVhPblYxMU1pZU0vNHRKVW9sNExmMSttdUlPZ05lVUo1MnJwYWNoTVdKbHRBY1RnejNjRmJHalp5Wk1FdXZNT2QzNWtNbFJlTlF4Rjdhc1FYa3RsYXdaT1RBazZjcXBMYVlvNXRBTzhiYWRhZ3BHTC8zSGJDOGIrdTdMMHJPTndHRWxCRElOVW5FbVltMXZPejhyY003NS84MU1Qekl0cWdkcGdFQzFTUEFrUTRGN3gxWjV1QmZ3VnFzNDVuMEtudEE0Y0pHdks3dTRUdmUvSFY2aEtnU0E1aHpCaXRaTG83NHNTdEVPMmQ4TzZ4Z0pqaDAyMHpGRi9OVzlueGppdVZZS01aVFZXV2hlajRrcndSSlMxOXNpUmdia2lPMThSYUF1dVhFRFFzZjljcmVQcXBaOHlKaVVzMVV6aXJMMnhIaW1QZXFBWFc3YkN4Y3o5Nlh3YTFxdk5nQnM2YXM4aDdqMm9pQU15REFuWFRValVpemtFZlRaeE84cFdIeSsrZ3MrZ0EvUFJRanlGU1o4alFETGRENTgzME5MQ0NCRG1uVEJKUmMzZjBJcVpFOHhlWTV6bFUzSElKcUpLeVNMS3d6bkFnTmF2WVRBcXpRQ1BFQkZPdjRRTFdUUzRnVThrdkxpejBWOWM1ZVdqSW5nVWJtbFlmTDdWNlpFN294akhGSlhJeFRUVEVyMFBrcmVMTWJHZVBzaml6MDRKRktsSjBwT3Yva2l0ZHorZE55TXBlbWtUck1rTitWL3NsMXhpaDlHZ0VZWGlYbFdXK2RUVFBNRVp2dHBzenQ1VjR3VWVIQ29GdFo2ZllYazNTbmZnTnlTZmVUUE9tU3c2QWNZL1AxK3JzaENvdityYjNNczQxSVJVQVhSQ2xyczVDTjBBZjZUWFZ0S0Y2clF1ZDlaZWtZS25KV0swN2lzL3d4eWdVa0xGWVYvRDBDM3FsSUNMKzV4aEhaVDFhR2JHM3p4bHFSc0p4Y0tzRDY0Q2JJS0NOamZ2dTFmWWxqZmNHd0VnN3VlRGRibHZ1aC9XTWJWN2xvcE5PcUdmOC9PWG1zb1E3RWFWdlNsSXg2VERndXhrckxIdzRSdUp2T3E1Nmh2V0NvZy90ckUyYnE4ZU01TSttenZWd2gvNGUyOWh2K3VWUGN6RXNxUzA0K2xsTnBZWnk1WHo2cisxK2xBYWh5MldaR1VlRndxZ2VRc0J3bHlTVlBCdm9GRTVGbUc5RldhUFBKN0pXbTVVUXVQK1Zld2VOVGx3Ni9Qai91cEc2ZXQ5amZqTEFCR25rZ1FSdzMyVnNINmJsVDdBWktaUTArREdXQXovMnBTSnlmN21TZ25pSTAxZG1PRGZhZVVReUp4bDQ5Ty9HYXFIQ1JqY0pJTGFOYlM0VnJRYmRwQUhxYUxVRkx0b3lLSkhyajJyLytENExXaXhDM0NJZ1BSUWM2QUJxWTdZeWdkZUk9fEVhWG8yem03Y2UyMlYyUnhocDNZNi9vMzBDUT0=; Max-Age=900; Expires=Sun, 30 Jun 2024 11:23:48 GMT; Domain=appleid.apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: itspod=; Max-Age=0; Expires=Thu, 01 Jan 1970 00:00:10 GMT; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: caw=MDAwNTA2LTA4LTFlMTkyMTE4LTc3NWQtNDA4NS04OTk1LTBhYTNmNWZlZTgyMTpXU1RLTmMwY2FjOWY1LWE1ZTUtNDk1ZS1hZmYzLTZlNzI4M2JkYmMyY1dGbGFweElGeU9meWZxT2pZajdQWTBXbm1kUmpOLzJoc3FJLy92Tmx2NGlDNFBSWWg0cm9JZHJIVHNXWFFqM0xYME93bk43Q0dVL0UzNFZMdFFseTF0K1ZYWmtleGlBbmNZdStNaFE5N1VWVzZwSDBpUGlHT1gwRzc1eEZER3hIVE1Mc3hUZTliRnRHV3EwdGJYbWtlMi8yQk1ZZEc2eWZqaFIrekU1NWl0TStuMDRNYW01TjRKVlg3MDVpbVBxMXNqMUtvZFVnWHlRV0ZnR3hWbnpGcEJ2blJjYkk2Ym9lWkhyU2w5OWZFZWlQajBGckE1MzlJZTN6MGl3d2tyZDZxdGd0akRZbFJaUWlWZlUycVFsR05CWE43QT09MHZpTnlzeVg5NjlkZmE3Yi1iMGEzLTQyM2UtYWNjMi04ZTg1OGRiNGY2YTE1ODU5NWE0YzA3ODhmYzliZjZmYjc1NjdkMDBhMjU4NzFjZjQyMDc0NjkwYjI1Y2FlOGFmMjAxODkwZTA1ZGU2ZDIyOTdjZThiOTk4YzBjNjUxNjM0ZTAzZTUxN2I2MWM2ZTVhODY0ZjZmZjYwZDgzYzZlMmQ5NjA0NWViYzRiOTk5YjlkMjU3YjQ0NTViMDhkZTY5MzNlNDM2MmVlZTNmMGRHbUFJbm52Mg==; Max-Age=300; Expires=Sun, 30 Jun 2024 11:13:48 GMT; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: caw-at=eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ.eyJzdWIiOiIwMDA1MDYtMDgtMWUxOTIxMTgtNzc1ZC00MDg1LTg5OTUtMGFhM2Y1ZmVlODIxIiwiaXNzIjoiY29tLmFwcGxlLmlkbXMuY2xpZW50IiwiZXhwIjoxNzE5NzQ2NjI4LCJpYXQiOjE3MTk3NDU3Mjh9.Y0kLQWtMtjxy1-7aeJABwfldjmOz0RMfwqbFjuo_6Sxp5F0C-kjLz3_kmb7gbPgKpvNVhuQZxhScUxH4DUn7Lw; Max-Age=900; Expires=Sun, 30 Jun 2024 11:23:48 GMT; Domain=apple.com; Path=/; Secure; HttpOnly
        //X-Apple-I-Request-Context: ca
        //Set-Cookie: aidsp=965CF80843A8CC65FCA3C7730997AC8741A87E8125EEA6F38FD3EE184F7E0F8057BD0DAE9A87F07F039B3600BBAFDE815BB08F34FCCC0F6FEA1DD7620E7A0888DB879610E830C525794FBC4ACEBE14CAB20B45C0513ADFE59E16612C5BC2AC93E9B89279148F46CE4A43B29FA9596F319FF81EEA9014DA76; Domain=appleid.apple.com; Path=/; Secure; HttpOnly
        //vary: accept-encoding
        //Host: appleid.apple.com
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
        //POST https://appleid.apple.com/authenticate/password HTTP/1.1
        //Connection: Keep-Alive
        //Content-Type: application/json; Charset=UTF-8
        //Accept: application/json, text/plain, */*
        //Accept-Language: zh-CN,zh;q=0.9
        //Cookie: idclient=web; dslang=CN-ZH; site=CHN; aidsp=965CF80843A8CC65FCA3C7730997AC8741A87E8125EEA6F38FD3EE184F7E0F8057BD0DAE9A87F07F039B3600BBAFDE815BB08F34FCCC0F6FEA1DD7620E7A0888DB879610E830C525794FBC4ACEBE14CAB20B45C0513ADFE59E16612C5BC2AC93E9B89279148F46CE4A43B29FA9596F319FF81EEA9014DA76; aasp=F80190F4FD80B689D2B92B383AC4B8C1EF55795E9C47C39C777D7E1AEC4452F12A219F6F540379F6A361BE1685C494DE38C6973A08A755B3EDA3F1326F3718C2B1C78BBFE5A455EA9D41C556D182961CC385907710EF70A3E8CEC61CA509CA668750EF60BD570D42021E94AA1A41BE2F5F3149E1C1B3AFAA; acn01=ZcT6K8g6hiycDKk/DzoSUlmT+dj134Hmy4OkJeCr9wA1QC8/Tjvk; myacinfo=DAWTKNV323952cf8084a204fb20ab2508441a07d02d356c9a9e48916520085c3cbc21c2532dfc92ea37f2d8ede62a39be232133c5b17365830bdd1d11fc83bdd5a077cd024d0195452d5b7ba7f82243d75b919028cbc500bc0bccdb8fe9a8ee5b548574bb8919822ecb359d2a8fed7bdd02f967b8edd17f48fbe2bf5326132af77fdb6a476db697aac8db8f32475f527d4340d326dca8bf65d54160ae2772374477146e989ad7d142166206ef25ae7da769621a574370f423021a807beebd2bbeea26471bf426bd3b64165a56fb77df9aa7bfb2082a541a43463d7d03097858f3a9cbba9705b7a10dadd57522ba6b0ff08ad4eba9ba5b58dc78b096c4f42fd8221b970a3ec848008da4e07f20a83483c7170657e121e9c4d24bca18cb6c54eb824c1e81d87b97d4f036aed61325476228026681244c319b05660d4e356f2a9d393763882b27a8524f28b0919ddd7416844f1bb795a74bac9c10ec1f7204ad2d92e0b74c07c5ded651dcc79b94b8b85db8c5497503c70245ea10a1e782572fcef7778ba8d4e7e293edfae2102b192d4fc099aa5510757200249cabfc4e0ea486972abfd8ee335a0190503148681e11e0037000c12b5194ca4b371fa4507ce9ca113a67d36ebf68ec81d01cd0035ec3963746ee9b90a31ab3716bc89d3df848d91be34cd9815260a4e1a8b4ee0f3df462e99f02e66657153b9ea5a8fe8acbb6fa7adb6e7a02e12cbb570a9b3975358e6fa343012d644b6d9452ea025b2e5d416171c5caf395e895b172c3c963f0542a1d21c952d48fc7d165250d84147083997c6b9de74a53b681ad2d963132e2699782e4a9b439193a61aeb695b45d1e350b2b6726efe8c488e10cfec4fd5b8c011ef5d5e0fc35cb4c54632741b5ada51c1fefa108e88c4b3fb5e28b0077ac075653269887fd337f4fc089c4a787c77aae122ebbcf849807df2e92244fc5333d8c74f56b8ca9b56403a7004bd4480ad17dfa2268a811b2fda3f585a47V3; awat=TURBd05UQTJMVEE0TFRGbE1Ua3lNVEU0TFRjM05XUXROREE0TlMwNE9UazFMVEJoWVRObU5XWmxaVGd5TVRveE56RTVOelExTnpJNE9URTJPakU9OlJrQVhPblYxMU1pZU0vNHRKVW9sNExmMSttdUlPZ05lVUo1MnJwYWNoTVdKbHRBY1RnejNjRmJHalp5Wk1FdXZNT2QzNWtNbFJlTlF4Rjdhc1FYa3RsYXdaT1RBazZjcXBMYVlvNXRBTzhiYWRhZ3BHTC8zSGJDOGIrdTdMMHJPTndHRWxCRElOVW5FbVltMXZPejhyY003NS84MU1Qekl0cWdkcGdFQzFTUEFrUTRGN3gxWjV1QmZ3VnFzNDVuMEtudEE0Y0pHdks3dTRUdmUvSFY2aEtnU0E1aHpCaXRaTG83NHNTdEVPMmQ4TzZ4Z0pqaDAyMHpGRi9OVzlueGppdVZZS01aVFZXV2hlajRrcndSSlMxOXNpUmdia2lPMThSYUF1dVhFRFFzZjljcmVQcXBaOHlKaVVzMVV6aXJMMnhIaW1QZXFBWFc3YkN4Y3o5Nlh3YTFxdk5nQnM2YXM4aDdqMm9pQU15REFuWFRValVpemtFZlRaeE84cFdIeSsrZ3MrZ0EvUFJRanlGU1o4alFETGRENTgzME5MQ0NCRG1uVEJKUmMzZjBJcVpFOHhlWTV6bFUzSElKcUpLeVNMS3d6bkFnTmF2WVRBcXpRQ1BFQkZPdjRRTFdUUzRnVThrdkxpejBWOWM1ZVdqSW5nVWJtbFlmTDdWNlpFN294akhGSlhJeFRUVEVyMFBrcmVMTWJHZVBzaml6MDRKRktsSjBwT3Yva2l0ZHorZE55TXBlbWtUck1rTitWL3NsMXhpaDlHZ0VZWGlYbFdXK2RUVFBNRVp2dHBzenQ1VjR3VWVIQ29GdFo2ZllYazNTbmZnTnlTZmVUUE9tU3c2QWNZL1AxK3JzaENvdityYjNNczQxSVJVQVhSQ2xyczVDTjBBZjZUWFZ0S0Y2clF1ZDlaZWtZS25KV0swN2lzL3d4eWdVa0xGWVYvRDBDM3FsSUNMKzV4aEhaVDFhR2JHM3p4bHFSc0p4Y0tzRDY0Q2JJS0NOamZ2dTFmWWxqZmNHd0VnN3VlRGRibHZ1aC9XTWJWN2xvcE5PcUdmOC9PWG1zb1E3RWFWdlNsSXg2VERndXhrckxIdzRSdUp2T3E1Nmh2V0NvZy90ckUyYnE4ZU01TSttenZWd2gvNGUyOWh2K3VWUGN6RXNxUzA0K2xsTnBZWnk1WHo2cisxK2xBYWh5MldaR1VlRndxZ2VRc0J3bHlTVlBCdm9GRTVGbUc5RldhUFBKN0pXbTVVUXVQK1Zld2VOVGx3Ni9Qai91cEc2ZXQ5amZqTEFCR25rZ1FSdzMyVnNINmJsVDdBWktaUTArREdXQXovMnBTSnlmN21TZ25pSTAxZG1PRGZhZVVReUp4bDQ5Ty9HYXFIQ1JqY0pJTGFOYlM0VnJRYmRwQUhxYUxVRkx0b3lLSkhyajJyLytENExXaXhDM0NJZ1BSUWM2QUJxWTdZeWdkZUk9fEVhWG8yem03Y2UyMlYyUnhocDNZNi9vMzBDUT0=; caw=MDAwNTA2LTA4LTFlMTkyMTE4LTc3NWQtNDA4NS04OTk1LTBhYTNmNWZlZTgyMTpXU1RLTmMwY2FjOWY1LWE1ZTUtNDk1ZS1hZmYzLTZlNzI4M2JkYmMyY1dGbGFweElGeU9meWZxT2pZajdQWTBXbm1kUmpOLzJoc3FJLy92Tmx2NGlDNFBSWWg0cm9JZHJIVHNXWFFqM0xYME93bk43Q0dVL0UzNFZMdFFseTF0K1ZYWmtleGlBbmNZdStNaFE5N1VWVzZwSDBpUGlHT1gwRzc1eEZER3hIVE1Mc3hUZTliRnRHV3EwdGJYbWtlMi8yQk1ZZEc2eWZqaFIrekU1NWl0TStuMDRNYW01TjRKVlg3MDVpbVBxMXNqMUtvZFVnWHlRV0ZnR3hWbnpGcEJ2blJjYkk2Ym9lWkhyU2w5OWZFZWlQajBGckE1MzlJZTN6MGl3d2tyZDZxdGd0akRZbFJaUWlWZlUycVFsR05CWE43QT09MHZpTnlzeVg5NjlkZmE3Yi1iMGEzLTQyM2UtYWNjMi04ZTg1OGRiNGY2YTE1ODU5NWE0YzA3ODhmYzliZjZmYjc1NjdkMDBhMjU4NzFjZjQyMDc0NjkwYjI1Y2FlOGFmMjAxODkwZTA1ZGU2ZDIyOTdjZThiOTk4YzBjNjUxNjM0ZTAzZTUxN2I2MWM2ZTVhODY0ZjZmZjYwZDgzYzZlMmQ5NjA0NWViYzRiOTk5YjlkMjU3YjQ0NTViMDhkZTY5MzNlNDM2MmVlZTNmMGRHbUFJbm52Mg==; caw-at=eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ.eyJzdWIiOiIwMDA1MDYtMDgtMWUxOTIxMTgtNzc1ZC00MDg1LTg5OTUtMGFhM2Y1ZmVlODIxIiwiaXNzIjoiY29tLmFwcGxlLmlkbXMuY2xpZW50IiwiZXhwIjoxNzE5NzQ2NjI4LCJpYXQiOjE3MTk3NDU3Mjh9.Y0kLQWtMtjxy1-7aeJABwfldjmOz0RMfwqbFjuo_6Sxp5F0C-kjLz3_kmb7gbPgKpvNVhuQZxhScUxH4DUn7Lw
        //Host: appleid.apple.com
        //Referer: https://appleid.apple.com/
        //User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36
        //scnt: AAAA-jk2NUNGODA4NDNBOENDNjVGQ0EzQzc3MzA5OTdBQzg3NDFBODdFODEyNUVFQTZGMzhGRDNFRTE4NEY3RTBGODA1N0JEMERBRTlBODdGMDdGMDM5QjM2MDBCQkFGREU4MTVCQjA4RjM0RkNDQzBGNkZFQTFERDc2MjBFN0EwODg4REI4Nzk2MTBFODMwQzUyNTc5NEZCQzRBQ0VCRTE0Q0FCMjBCNDVDMDUxM0FERkU1OUUxNjYxMkM1QkMyQUM5M0U5Qjg5Mjc5MTQ4RjQ2Q0U0QTQzQjI5RkE5NTk2RjMxOUZGODFFRUE5MDE0REE3NnwyAAABkGjjNEU9qvzt34qBqyUcm12cKxpIPW5lGcM-hCCv4gz0EBPCVGQNTopxZaM5ABqY7Yynh0-WHjMgUcAW0WQTia9DxzP5QGilmN8NHbOncX_28awVMQ
        //X-Apple-I-Request-Context: ca
        //X-Apple-I-TimeZone: Asia/Shanghai
        //Origin: https://appleid.apple.com
        //Sec-Fetch-Site: same-origin
        //Sec-Fetch-Mode: cors
        //Sec-Fetch-Dest: empty
        //Content-Length: 23
        //
        //{"password":"Ad898989"}

        $response = $this->appleidRequest('POST', '/authenticate/password', [
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
        //HTTP/1.1 204
        //Server: Apple
        //Date: Sun, 30 Jun 2024 11:08:49 GMT
        //Connection: keep-alive
        //X-Apple-I-Request-ID: 208693a2-36d1-11ef-8121-9be32adf0ac7
        //Set-Cookie: idclient=web; Domain=appleid.apple.com; Path=/; Secure; HttpOnly
        //X-BuildVersion: R12_1
        //X-FRAME-OPTIONS: DENY
        //X-Content-Type-Options: nosniff
        //X-XSS-Protection: 1; mode=block
        //Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
        //Content-Security-Policy: default-src 'self' ; connect-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com https://api.apple-cloudkit.com https://feedbackws.apple-cloudkit.com https://*.icloud-content.com https://*.icloud-content.com.cn ; font-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com ; frame-src 'self' https://idmsa.apple.com https://signin.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://familyws.icloud.apple.com  https://apps.apple.com ; img-src 'self' https://www.apple.com https://appleid.cdn-apple.com data: https://*.mzstatic.com https://appleid.apple.com https://*.icloud.com ; media-src data: ; object-src 'none' ; script-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://signin.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://appleid.apple.com ; style-src 'unsafe-inline' 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com ;
        //Referrer-Policy: origin
        //Set-Cookie: dslang=CN-ZH; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: site=CHN; Domain=apple.com; Path=/; Secure; HttpOnly
        //Pragma: no-cache
        //Expires: Thu, 01 Jan 1970 00:00:00 GMT
        //Cache-Control: no-cache
        //Cache-Control: no-store
        //Set-Cookie: caw-at=eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ.eyJzdWIiOiIwMDA1MDYtMDgtMWUxOTIxMTgtNzc1ZC00MDg1LTg5OTUtMGFhM2Y1ZmVlODIxIiwibHZwY3RzIjoxNzE5NzQ1NzI5NDk2LCJpc3MiOiJjb20uYXBwbGUuaWRtcy5jbGllbnQiLCJleHAiOjE3MTk3NDY2MjksImlhdCI6MTcxOTc0NTcyOX0.l4ssxuuXLxb9cJNemabRzD1k2427Bd6e6UtiijyDB5FCbe5aY3c-oL6kX75Xxvq-icgN2ziATuG9xsoJ5YJXZw; Max-Age=900; Expires=Sun, 30 Jun 2024 11:23:49 GMT; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: awat=TURBd05UQTJMVEE0TFRGbE1Ua3lNVEU0TFRjM05XUXROREE0TlMwNE9UazFMVEJoWVRObU5XWmxaVGd5TVRveE56RTVOelExTnpJNU5EazVPakk9OmIyT3VCS1ZUeVUvSXpQOS84OGRNUWVONU9nbDNkK0trQUZEWi9ZNUFWV2pUZHJ6TW1oM0ZNQlZscHAzc1lKbklXT1JlaURpWmEvRFYzZFlScWQwTFZyK0lMY1ZFQUhoMWxJbllOdXdxaUpCVS9aWXZhejB6WURwTmEwUXJVWWlLQXQ1SW53RU1pYlJ0Qm1jNUNiT2pHR3Fja3E4ZUtHYXFKdmRwOGZkNk1IeEhzVFZXRG91amJxMFRrM3hHMHdUU3B4a2s5QTVITmVpOGJQVGhFSFM2c3g3VHRnMENEM2JvbnRxMG54TWU2bFZKVDZFVFo5NHJyOEZIZzQ5cldhdUI3OWV5RFVZTzQ0cG1nd3AyREdzRXNLcm5wYVhkUmdNbHI5TXdPa1gxcGQrR0o1Y3BBazRhUC9ZaUprdlRMQjgya01CSFYxa1FXYmsxcWtrckRYeUFqWEFyQmk3NkhDVVNpbVRuZG9ZZUlHdEZ6OUp3Sm51ODAwRUt5Y0JzcGE5WmtWSzZjZ21BRU5kSytERXlzOENPL3lMd1Z0VGN5NkxHR2diMC9EaFpSeDlYZkNTUTJzazFJZ3FQOElHN1ZDUXVxRlVRRjRKNHpnU2xoWC8zMS81SkpVekhydUpqVFJvMGIvd2xoNlBDdHo2QlJERVA3VjdGOXRKcWNmeDhqZ1N2aHRPVElJbWhsaUtxQzFoTC9zemNRbUVWNXJ0Q0ZPVy9PVXdxM1RKbjRWdWU2WWxQUnlrTnppZjdCNExoWG54Q2prMEVuaWRJM0hhdCtUSkJReVBJOTFuWVdabFM3eTkxVXpibzRFclc1SWhPcHFGWnBPWFRTZjdMR0t0Q2dZZXlVSUN5TjRIeWlKNGRzbytvalZUSVZ1bWxNc204RmhpNzNpaVZoR24vWWN3UWlZTEhCSk1SdnVjQ1NES1BRaUs3cUFIeGhXWXBtVWJTRWwrNzV4ODlFa2UxUW5SeEMvNm9pc0ZweWZ1YWdTenJ3dHFFNFdXMm0vdE9DYWx2aEhFTU12R2U0SzZvSGNLeWtycmRudkZVR1JKbm44NXVTQmIzUEJoSzZCMzN1REsxanhKT2xjMFJEZ1ArcVJCbzJZMEtNRmJCY3lYNnIzdkdwVm4wd2VWbk51T0FYQUNnZFlmeDN3c1o0dUdNQmV5VjRpOHg3d2FJTXU3eEJxL0Y2ZERZS3pSQlY3bGFkNncwSXJwcEdKbUY4VnBuVVJNUFpqZGdsVzUxQmtCNFdjME1aTUFNWVFwSzNPNUpNbTRHU0o4NHZ4OUloelkrM0s2UzNDYmd4ZDdkNzU3NGJuQUtpc0ZNTjJiSDF1ZFVxRGNlMVRleXM1MjN2aWNZS3BYWXF6b2VLbkozc3pEdmM2YlRtZjdyUnhtbmVoZzBEbUxwNUwyR1VlWHMxd2xsQ1RJNHROS1l1WEJ1Qkdlek5qWmdiMkdGQUJtQWp0bEQrb1E9fFlqeWk1blFDb1piR3JsSDJ2WDFNVUdqU3BzMD0=; Max-Age=900; Expires=Sun, 30 Jun 2024 11:23:49 GMT; Domain=appleid.apple.com; Path=/; Secure; HttpOnly
        //X-Apple-I-Request-Context: ca
        //Host: appleid.apple.com
    }

    /**
     * 绑定手机号码
     * @param string $phoneNumber
     * @param string $countryCode
     * @param string $countryDialCode
     * @param bool $nonFTEU
     * @return Response
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function bindPhoneSecurityVerify(
        string $phoneNumber,
        string $countryCode,
        string $countryDialCode,
        bool $nonFTEU = true
    ): Response {
        //POST https://appleid.apple.com/account/manage/security/verify/phone HTTP/1.1
//        POST https://appleid.apple.com/account/manage/security/verify/phone HTTP/2
        //Connection: Keep-Alive
        //Content-Type: application/json; Charset=UTF-8
        //Accept: application/json, text/plain, */*
        //Accept-Language: zh-CN,zh;q=0.9
        //Cookie: idclient=web; dslang=CN-ZH; site=CHN; aidsp=965CF80843A8CC65FCA3C7730997AC8741A87E8125EEA6F38FD3EE184F7E0F8057BD0DAE9A87F07F039B3600BBAFDE815BB08F34FCCC0F6FEA1DD7620E7A0888DB879610E830C525794FBC4ACEBE14CAB20B45C0513ADFE59E16612C5BC2AC93E9B89279148F46CE4A43B29FA9596F319FF81EEA9014DA76; aasp=F80190F4FD80B689D2B92B383AC4B8C1EF55795E9C47C39C777D7E1AEC4452F12A219F6F540379F6A361BE1685C494DE38C6973A08A755B3EDA3F1326F3718C2B1C78BBFE5A455EA9D41C556D182961CC385907710EF70A3E8CEC61CA509CA668750EF60BD570D42021E94AA1A41BE2F5F3149E1C1B3AFAA; acn01=ZcT6K8g6hiycDKk/DzoSUlmT+dj134Hmy4OkJeCr9wA1QC8/Tjvk; myacinfo=DAWTKNV323952cf8084a204fb20ab2508441a07d02d356c9a9e48916520085c3cbc21c2532dfc92ea37f2d8ede62a39be232133c5b17365830bdd1d11fc83bdd5a077cd024d0195452d5b7ba7f82243d75b919028cbc500bc0bccdb8fe9a8ee5b548574bb8919822ecb359d2a8fed7bdd02f967b8edd17f48fbe2bf5326132af77fdb6a476db697aac8db8f32475f527d4340d326dca8bf65d54160ae2772374477146e989ad7d142166206ef25ae7da769621a574370f423021a807beebd2bbeea26471bf426bd3b64165a56fb77df9aa7bfb2082a541a43463d7d03097858f3a9cbba9705b7a10dadd57522ba6b0ff08ad4eba9ba5b58dc78b096c4f42fd8221b970a3ec848008da4e07f20a83483c7170657e121e9c4d24bca18cb6c54eb824c1e81d87b97d4f036aed61325476228026681244c319b05660d4e356f2a9d393763882b27a8524f28b0919ddd7416844f1bb795a74bac9c10ec1f7204ad2d92e0b74c07c5ded651dcc79b94b8b85db8c5497503c70245ea10a1e782572fcef7778ba8d4e7e293edfae2102b192d4fc099aa5510757200249cabfc4e0ea486972abfd8ee335a0190503148681e11e0037000c12b5194ca4b371fa4507ce9ca113a67d36ebf68ec81d01cd0035ec3963746ee9b90a31ab3716bc89d3df848d91be34cd9815260a4e1a8b4ee0f3df462e99f02e66657153b9ea5a8fe8acbb6fa7adb6e7a02e12cbb570a9b3975358e6fa343012d644b6d9452ea025b2e5d416171c5caf395e895b172c3c963f0542a1d21c952d48fc7d165250d84147083997c6b9de74a53b681ad2d963132e2699782e4a9b439193a61aeb695b45d1e350b2b6726efe8c488e10cfec4fd5b8c011ef5d5e0fc35cb4c54632741b5ada51c1fefa108e88c4b3fb5e28b0077ac075653269887fd337f4fc089c4a787c77aae122ebbcf849807df2e92244fc5333d8c74f56b8ca9b56403a7004bd4480ad17dfa2268a811b2fda3f585a47V3; awat=TURBd05UQTJMVEE0TFRGbE1Ua3lNVEU0TFRjM05XUXROREE0TlMwNE9UazFMVEJoWVRObU5XWmxaVGd5TVRveE56RTVOelExTnpJNU5EazVPakk9OmIyT3VCS1ZUeVUvSXpQOS84OGRNUWVONU9nbDNkK0trQUZEWi9ZNUFWV2pUZHJ6TW1oM0ZNQlZscHAzc1lKbklXT1JlaURpWmEvRFYzZFlScWQwTFZyK0lMY1ZFQUhoMWxJbllOdXdxaUpCVS9aWXZhejB6WURwTmEwUXJVWWlLQXQ1SW53RU1pYlJ0Qm1jNUNiT2pHR3Fja3E4ZUtHYXFKdmRwOGZkNk1IeEhzVFZXRG91amJxMFRrM3hHMHdUU3B4a2s5QTVITmVpOGJQVGhFSFM2c3g3VHRnMENEM2JvbnRxMG54TWU2bFZKVDZFVFo5NHJyOEZIZzQ5cldhdUI3OWV5RFVZTzQ0cG1nd3AyREdzRXNLcm5wYVhkUmdNbHI5TXdPa1gxcGQrR0o1Y3BBazRhUC9ZaUprdlRMQjgya01CSFYxa1FXYmsxcWtrckRYeUFqWEFyQmk3NkhDVVNpbVRuZG9ZZUlHdEZ6OUp3Sm51ODAwRUt5Y0JzcGE5WmtWSzZjZ21BRU5kSytERXlzOENPL3lMd1Z0VGN5NkxHR2diMC9EaFpSeDlYZkNTUTJzazFJZ3FQOElHN1ZDUXVxRlVRRjRKNHpnU2xoWC8zMS81SkpVekhydUpqVFJvMGIvd2xoNlBDdHo2QlJERVA3VjdGOXRKcWNmeDhqZ1N2aHRPVElJbWhsaUtxQzFoTC9zemNRbUVWNXJ0Q0ZPVy9PVXdxM1RKbjRWdWU2WWxQUnlrTnppZjdCNExoWG54Q2prMEVuaWRJM0hhdCtUSkJReVBJOTFuWVdabFM3eTkxVXpibzRFclc1SWhPcHFGWnBPWFRTZjdMR0t0Q2dZZXlVSUN5TjRIeWlKNGRzbytvalZUSVZ1bWxNc204RmhpNzNpaVZoR24vWWN3UWlZTEhCSk1SdnVjQ1NES1BRaUs3cUFIeGhXWXBtVWJTRWwrNzV4ODlFa2UxUW5SeEMvNm9pc0ZweWZ1YWdTenJ3dHFFNFdXMm0vdE9DYWx2aEhFTU12R2U0SzZvSGNLeWtycmRudkZVR1JKbm44NXVTQmIzUEJoSzZCMzN1REsxanhKT2xjMFJEZ1ArcVJCbzJZMEtNRmJCY3lYNnIzdkdwVm4wd2VWbk51T0FYQUNnZFlmeDN3c1o0dUdNQmV5VjRpOHg3d2FJTXU3eEJxL0Y2ZERZS3pSQlY3bGFkNncwSXJwcEdKbUY4VnBuVVJNUFpqZGdsVzUxQmtCNFdjME1aTUFNWVFwSzNPNUpNbTRHU0o4NHZ4OUloelkrM0s2UzNDYmd4ZDdkNzU3NGJuQUtpc0ZNTjJiSDF1ZFVxRGNlMVRleXM1MjN2aWNZS3BYWXF6b2VLbkozc3pEdmM2YlRtZjdyUnhtbmVoZzBEbUxwNUwyR1VlWHMxd2xsQ1RJNHROS1l1WEJ1Qkdlek5qWmdiMkdGQUJtQWp0bEQrb1E9fFlqeWk1blFDb1piR3JsSDJ2WDFNVUdqU3BzMD0=; caw=MDAwNTA2LTA4LTFlMTkyMTE4LTc3NWQtNDA4NS04OTk1LTBhYTNmNWZlZTgyMTpXU1RLTmMwY2FjOWY1LWE1ZTUtNDk1ZS1hZmYzLTZlNzI4M2JkYmMyY1dGbGFweElGeU9meWZxT2pZajdQWTBXbm1kUmpOLzJoc3FJLy92Tmx2NGlDNFBSWWg0cm9JZHJIVHNXWFFqM0xYME93bk43Q0dVL0UzNFZMdFFseTF0K1ZYWmtleGlBbmNZdStNaFE5N1VWVzZwSDBpUGlHT1gwRzc1eEZER3hIVE1Mc3hUZTliRnRHV3EwdGJYbWtlMi8yQk1ZZEc2eWZqaFIrekU1NWl0TStuMDRNYW01TjRKVlg3MDVpbVBxMXNqMUtvZFVnWHlRV0ZnR3hWbnpGcEJ2blJjYkk2Ym9lWkhyU2w5OWZFZWlQajBGckE1MzlJZTN6MGl3d2tyZDZxdGd0akRZbFJaUWlWZlUycVFsR05CWE43QT09MHZpTnlzeVg5NjlkZmE3Yi1iMGEzLTQyM2UtYWNjMi04ZTg1OGRiNGY2YTE1ODU5NWE0YzA3ODhmYzliZjZmYjc1NjdkMDBhMjU4NzFjZjQyMDc0NjkwYjI1Y2FlOGFmMjAxODkwZTA1ZGU2ZDIyOTdjZThiOTk4YzBjNjUxNjM0ZTAzZTUxN2I2MWM2ZTVhODY0ZjZmZjYwZDgzYzZlMmQ5NjA0NWViYzRiOTk5YjlkMjU3YjQ0NTViMDhkZTY5MzNlNDM2MmVlZTNmMGRHbUFJbm52Mg==; caw-at=eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ.eyJzdWIiOiIwMDA1MDYtMDgtMWUxOTIxMTgtNzc1ZC00MDg1LTg5OTUtMGFhM2Y1ZmVlODIxIiwibHZwY3RzIjoxNzE5NzQ1NzI5NDk2LCJpc3MiOiJjb20uYXBwbGUuaWRtcy5jbGllbnQiLCJleHAiOjE3MTk3NDY2MjksImlhdCI6MTcxOTc0NTcyOX0.l4ssxuuXLxb9cJNemabRzD1k2427Bd6e6UtiijyDB5FCbe5aY3c-oL6kX75Xxvq-icgN2ziATuG9xsoJ5YJXZw
        //Host: appleid.apple.com
        //Referer: https://appleid.apple.com/
        //User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36
        //scnt: AAAA-jk2NUNGODA4NDNBOENDNjVGQ0EzQzc3MzA5OTdBQzg3NDFBODdFODEyNUVFQTZGMzhGRDNFRTE4NEY3RTBGODA1N0JEMERBRTlBODdGMDdGMDM5QjM2MDBCQkFGREU4MTVCQjA4RjM0RkNDQzBGNkZFQTFERDc2MjBFN0EwODg4REI4Nzk2MTBFODMwQzUyNTc5NEZCQzRBQ0VCRTE0Q0FCMjBCNDVDMDUxM0FERkU1OUUxNjYxMkM1QkMyQUM5M0U5Qjg5Mjc5MTQ4RjQ2Q0U0QTQzQjI5RkE5NTk2RjMxOUZGODFFRUE5MDE0REE3NnwyAAABkGjjNEU9qvzt34qBqyUcm12cKxpIPW5lGcM-hCCv4gz0EBPCVGQNTopxZaM5ABqY7Yynh0-WHjMgUcAW0WQTia9DxzP5QGilmN8NHbOncX_28awVMQ
        //X-Apple-I-Request-Context: ca
        //X-Apple-I-TimeZone: Asia/Shanghai
        //Origin: https://appleid.apple.com
        //Sec-Fetch-Site: same-origin
        //Sec-Fetch-Mode: cors
        //Sec-Fetch-Dest: empty
        //Content-Length: 140
        //
        //{"phoneNumberVerification":{"phoneNumber":{"countryCode":"US","number":"(945) 313-0454","countryDialCode":"1","nonFTEU":true},"mode":"sms"}}

        return $this->appleidRequest('POST', '/account/manage/security/verify/phone', [

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
        //HTTP/1.1 200
        //Server: Apple
        //Date: Tue, 16 Jul 2024 08:09:29 GMT
        //Content-Type: application/json;charset=UTF-8
        //X-Apple-I-Request-ID: b98b4e67-434a-11ef-b370-812e145baf59
        //X-BuildVersion: R13
        //X-FRAME-OPTIONS: DENY
        //X-Content-Type-Options: nosniff
        //X-XSS-Protection: 1; mode=block
        //Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
        //Content-Security-Policy: default-src 'self' ; connect-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com https://api.apple-cloudkit.com https://feedbackws.apple-cloudkit.com https://*.icloud-content.com https://*.icloud-content.com.cn ; font-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com ; frame-src 'self' https://idmsa.apple.com https://signin.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://familyws.icloud.apple.com  https://apps.apple.com ; img-src 'self' https://www.apple.com https://appleid.cdn-apple.com data: https://*.mzstatic.com https://appleid.apple.com https://*.icloud.com ; media-src data: ; object-src 'none' ; script-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://signin.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://appleid.apple.com ; style-src 'unsafe-inline' 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com ;
        //Referrer-Policy: origin
        //scnt: AAAA-kU4RjQ2NkY3RUE5M0E2MzdFQzUwRkY4MjIwNjdEMDkzQjBBRjEzRTc3ODRBRkI3Q0YxODMwMjY2NzIyMzc3MUNDNUYwRjA3RDhBN0IzRDBCMTkwNTE4MTlCMzQ0MkM3QUJDRDNFQTk2QUY4QTE0MEY1RTc1M0VDNTAyNjc1NzJFMjVFNTUxQjJEN0M1ODZCNzk0NjAzODA3OTM0NzY5OTgwRUE2OTE5RERBNjY3NkJCNEQ4Qzk0Q0U0QjJDOTg2NzBDNjZBMTNEQjVGQTE5NzkzMDYyNTQ1MjJBQjk3MzY2NEE3MEVCNzc3M0M4NDY2M3w5AAABkLqkxehPDrQnDHZGyhWKo0G8c2UOctvI-Ie5WJzCXhfMgh2jpzluTbA3dXfqAB0ToaQi3yM77aJFhQFaRcChyKtI4OaPjXbL7Ya5tZEUiqYtqjZgXg
        //Set-Cookie: dslang=CN-ZH; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: site=CHN; Domain=apple.com; Path=/; Secure; HttpOnly
        //Pragma: no-cache
        //Expires: Thu, 01 Jan 1970 00:00:00 GMT
        //Cache-Control: no-cache
        //Cache-Control: no-store
        //Set-Cookie: crsc=0IbnScgfxG349r860FmSkB3hqHLGfIoTArMrMnmEfrmcIAt2IejTrudpNQ+oLwalXrbBJI+jMoVMU/U1vFP2V9oAHROhwOsE7Q==; Max-Age=900000; Expires=Fri, 26 Jul 2024 18:09:29 GMT; Domain=appleid.apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: caw-at=eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ.eyJzdWIiOiIwMDE1ODItMTAtZDAyZDc3ZGUtZDFmZS00M2QyLWFlYjItM2Y3MWU5YTE2OWM3IiwibHZwY3RzIjoxNzIxMTE3MzY3ODIzLCJpc3MiOiJjb20uYXBwbGUuaWRtcy5jbGllbnQiLCJleHAiOjE3MjExMTgyNjksImlhdCI6MTcyMTExNzM2OX0.-WwY_r4EjvVmXrxEgy1z6oAosd_GSQNsG7pnKmOgAJXVd2A43V7R9Ot33pl1lqROAlkb68wRz69cy_zuZzRZ0A; Max-Age=900; Expires=Tue, 16 Jul 2024 08:24:29 GMT; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: awat=TURBeE5UZ3lMVEV3TFdRd01tUTNOMlJsTFdReFptVXRORE5rTWkxaFpXSXlMVE5tTnpGbE9XRXhOamxqTnpveE56SXhNVEUzTXpZNU5qTXdPalk9OjUwbzlNck1LTm9UL0VvVUZyM09nZUVaRzNPQkMyOEljMk9CSE9xK0lnWkZtUW9tUDQ3ejlBSHZtQWVaZWZCM2Y5QWswNThKSjZpazd4Vm1NUGxpcXV1MWlESkZ4cjJuSHRYZDlXMG5EOXVLN2JlaEZPZVZsUTRlQ0dYaUZiUU9HSXRtbjNRNndpTHNnNmxaZjFteEFSbDk3dlVpSnVpQktHRS9TT3dwM2ltRjdNSXF1WVBNeGlCa2lta3orN2NBWnBLQlZVT3FHYTFOK1hZQ0lDNVlma2lkRjhjc3hwd3ZqalRBU2RZbC81alRRcmJ5Q0dlVW5NSGE2UGpsRmdwNGVDT2R6Zk5paWVTYUFmVUNnUFlDYzJWalFWUXY5TEo0bVRyem9raGVhZTJSVHZ2S2FsNFBYdHlmcEkxZW5iV3BTUitpZTVZcEdtWC8yNlQ5VTJTZk4zN3NDRUYxSUlib2pIbWxpaUd6K0RCTm52Y2xiSkd3bUhBVE50N2d1TzdtVFF3OHRkZ0xHVnhJYmQvbExMaWdVZmIraUdHRDBna29Zcy9uQnVVZ0FYcEROaVZqY1NzTWVOajRkNGNqdGRhSGpVemJZTHdzWElLdzBTT24vT1krRkVTYjV1bHZuVEV4SGd3TDlvRjN1YWVnYzZ6cnRlM1FDUitRenZyMjJ1bTJDZzVsREc2RkdVYjNiWWJKNVlnb0IrMS9JMHJwbkg1NVJuV3VXd010TXhENlpNeEoxZUk3THQ2RGo1d3M1emVoRS8zcHFTSVczdzB0Ykl6RFRXczM5QUd6SXM2aUMraUg2SFpWckVvZ1BmemxaRlE3VUVWSHBaL2xyeEtGNkhJTWxxRS9DcjFJS0VDeU5uNjVlRzg5RG1WeUZLeFBuOHY3OVRISHB4MkV1NU5PbVNYSU55K0lyMFpDdHIwbVU5TkxES2IrdFV3UDJFN28wN1JhUnZOdlJUeFJLTzRQMldreTRjQUlEdGNSa2o0UkVxcXdtUFI5ckxJTUxrNVhnQkJmN1pZUXFIQ2wrVmRpblo2M0Q0SWpJTWcvYWtGdnAwMjdsTWV4d1FnQ2F2Ykt6cGZDZXdmajNrTmM1YldPcmJWdmFHV3FudWhmSnozS1NxU1hNU2FhdHhoSmVCckxkWnk4NjNPY3VpNjAvWXIxU3dQcUpMaXhLclF2UmFMQVdqNWlXSU5YQzhqdFF5VzdlUUd2RktjQ3ZLc3VRUis4NHdVWkxSRFR3WHFqTTRmbjdtVWF5d3FnbUxjRm9zdzE0RG5wU0Z3VWZBdE5HOEdvM0lqQ003ZHEwYjF5bUdmeGRrWGpaT2l5dmczcEQrM3ZrVCtuZThiMVhoYjZSRkMwUnc2NW9KK0gwdXpEMms5QlRXZmJKT2p6aHkvK21yQW1ZZTNLLzlWTm1jRWk1V2NOYkl0aU93Tmd2eUkzU01hTVRPbmhNRlFBZEU2SEJGRWc2fG9zTVBRTElLcGRZM3pqWDlZVTBtQm5WSHg5OD0=; Max-Age=900; Expires=Tue, 16 Jul 2024 08:24:29 GMT; Domain=appleid.apple.com; Path=/; Secure; HttpOnly
        //X-Apple-I-Request-Context: ca
        //Cache-Control: no-store
        //vary: accept-encoding
        //Content-Encoding: gzip
        //Content-Language: zh-CN-x-lvariant-CHN
        //Host: appleid.apple.com
        //Content-Length: 10233
        //
        //{
        //  "phoneNumberVerification" : {
        //    "phoneNumber" : {
        //      "sameAsAppleID" : false,
        //      "nonFTEU" : true,
        //      "numberWithDialCode" : "+852 6330 3506",
        //      "deviceType" : false,
        //      "iMessageType" : false,
        //      "pacType" : false,
        //      "complianceType" : false,
        //      "appleComplianceType" : false,
        //      "numberWithDialCodeAndExtension" : "+852 6330 3506",
        //      "rawNumber" : "63303506",
        //      "fullNumberWithCountryPrefix" : "+852 6330 3506",
        //      "verified" : false,
        //      "countryCode" : "HK",
        //      "pushMode" : "sms",
        //      "countryDialCode" : "852",
        //      "number" : "63303506",
        //      "vetted" : false,
        //      "createDate" : "07/16/2024 08:09:29 AM",
        //      "updateDate" : "07/16/2024 08:09:29 AM",
        //      "rawNumberWithDialCode" : "+85263303506",
        //      "pending" : true,
        //      "lastTwoDigits" : "06",
        //      "loginHandle" : false,
        //      "countryCodeAsString" : "HK",
        //      "obfuscatedNumber" : "•••• ••06",
        //      "name" : "+852 6330 3506",
        //      "id" : 20101
        //    },
        //    "securityCode" : {
        //      "length" : 6,
        //      "tooManyCodesSent" : false,
        //      "tooManyCodesValidated" : false,
        //      "securityCodeLocked" : false,
        //      "securityCodeCooldown" : false
        //    },
        //    "mode" : "sms",
        //    "type" : "verification",
        //    "authenticationType" : "hsa2",
        //    "showAutoVerificationUI" : false,
        //    "countryCode" : "HK",
        //    "countryDialCode" : "852",
        //    "number" : "63303506",
        //    "keepUsing" : false,
        //    "changePhoneNumber" : false,
        //    "simSwapPhoneNumber" : false,
        //    "addDifferent" : false
        //  },

    }

    /**
     * 通过第三方获取手机接受验证码
     * @param string $url
     * @return Response|null
     * @throws GuzzleException
     */
    public function getPhoneTokenCode(string $url): ?Response
    {
        $response = $this->getClient()->get($url);

        if (empty($body = (string) $response->getBody())) {
            return null;
        }

        if (($code = $this->platformADecode($body)) === null)
        {
            if (($code = $this->platformBDecode($body)) === null) {
                return null;
            }
        }

        return new Response(response: $response,status:$response->getStatusCode(),data:  ['code' => $code]);
    }

    //平台
    protected function platformADecode(string $body): ?string
    {
        return Common::extractSixDigitNumber($body);
    }

    protected function platformBDecode(string $value): ?string
    {
        // 去除可能存在的额外引号
        $body = trim($value, '"');

        // 解码转义的引号
        $body = stripslashes($body);

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Code decode error', ['error' => json_last_error_msg(), 'body' => $value]);
            return null;
        }

        return empty($data['data']) ? null : Common::extractSixDigitNumber($data['data']);
    }

    /**
     * @param string $url
     * @param int $attempts
     * @param int $sleep
     * @return Response|null
     * @throws GuzzleException|AttemptBindPhoneCodeException
     */
    public function attemptGetPhoneCode(string $url, int $attempts = 6, int $sleep = 5): ?Response
    {
        for ($i = 0; $i < $attempts; $i++) {
            if ($response = $this->getPhoneTokenCode($url)) {
                return $response;
            }
            sleep($sleep);
        }
        throw new AttemptBindPhoneCodeException("Attempt {$attempts} times failed to get phone code");
    }


    /**
     * 重新发送验证码（邮箱验证码）
     * @return Response
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function sendSecurityCode(): Response
    {
        //PUT https://idmsa.apple.com/appleauth/auth/verify/trusteddevice/securitycode HTTP/1.1
        //PUT https://idmsa.apple.com/appleauth/auth/verify/trusteddevice/securitycode HTTP/1.1
        //Connection: Keep-Alive
        //Content-Type: application/json
        //Accept: application/json
        //Accept-Language: zh-CN,zh;q=0.9
        //Cookie: idclient=web; dslang=CN-ZH; site=CHN; aidsp=730651CFE0555376E310DE44BFE2E2AEB1B8AC466214EA9B759034F4D66AB523761C2DE836D8482D08719E90CF29B9EA7CA36EFB1D3BAFC687BFE8990185799EEE5055CFFAB665585E9812721E78FA1285093E7BEEAA2542209999BF7F97DF18F4D6488C9AB428486767B39D5F7C94668D0B025D344DD2E4; aasp=2BACAE772D777FB63F38BA744564D3426B12F4762B8E71D61B43361582FBB76C8E73C2E70A782F49301B7F2DFF98F58D25FC9B5AB9B630E0AC22B8C38D600695626FAD4B593E8141EDA388BF32955D08905EF764843366E67033F676DC0A20FF205247879D6AE36F98B967E7CB47E3871A27DC04408BEBC5; acn01=pVb3YvyL9Pr1W3DtxV3xFrtAH7fLWubVFxhIbe8AHLLzu3u93g==
        //Host: idmsa.apple.com
        //Referer: https://idmsa.apple.com/
        //User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36
        //scnt: AAAA-jJCQUNBRTc3MkQ3NzdGQjYzRjM4QkE3NDQ1NjREMzQyNkIxMkY0NzYyQjhFNzFENjFCNDMzNjE1ODJGQkI3NkM4RTczQzJFNzBBNzgyRjQ5MzAxQjdGMkRGRjk4RjU4RDI1RkM5QjVBQjlCNjMwRTBBQzIyQjhDMzhENjAwNjk1NjI2RkFENEI1OTNFODE0MUVEQTM4OEJGMzI5NTVEMDg5MDVFRjc2NDg0MzM2NkU2NzAzM0Y2NzZEQzBBMjBGRjIwNTI0Nzg3OUQ2QUUzNkY5OEI5NjdFN0NCNDdFMzg3MUEyN0RDMDQ0MDhCRUJDNXwxAAABkIuhV2VYQwkG4F3mMpYrPrEhoAbqtTxYpcBSoI0Ly1LbbJvbqzyQ-Szu-ByEAByy867AhN35H3rPqvoNrxjDgfr7SSRpljvjQ1SCeG2CHw3t8GOJOw
        //X-Apple-Widget-Key: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Apple-OAuth-Redirect-URI: https://appleid.apple.com
        //X-Apple-OAuth-Client-Id: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Apple-OAuth-Client-Type: firstPartyAuth
        //x-requested-with: XMLHttpRequest
        //X-Apple-ID-Session-Id: 2BACAE772D777FB63F38BA744564D3426B12F4762B8E71D61B43361582FBB76C8E73C2E70A782F49301B7F2DFF98F58D25FC9B5AB9B630E0AC22B8C38D600695626FAD4B593E8141EDA388BF32955D08905EF764843366E67033F676DC0A20FF205247879D6AE36F98B967E7CB47E3871A27DC04408BEBC5
        //X-Apple-Auth-Attributes: e55JW+RrHmpb/eBHNdcr0HxXZMoPdWawenSEph16Gqho/7l7YGnSfALw/EDBp9BcAZ0AQ+YG35neE8vnTWsGpuQ+7xG68E7as9qMfqAq3pLZbjEdmQvA4pzgqPTm1Mz7SUN+RqJRlWrlKgoKMqmLJBcd2iuNTZgYc0EndquQN7JFncKNvR974/De8iSGnKs3VO5S97IwNRM1HAAcsvO7hfgf
        //X-Apple-OAuth-Response-Type: code
        //X-Apple-OAuth-Response-Mode: web_message
        //X-Apple-Domain-Id: 1
        //Origin: https://idmsa.apple.com
        //Sec-Fetch-Site: same-origin
        //Sec-Fetch-Mode: cors
        //Sec-Fetch-Dest: empty
        //Content-Length: 0
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
        //HTTP/1.1 202
        //Server: Apple
        //Date: Sun, 07 Jul 2024 05:05:19 GMT
        //Content-Type: application/json;charset=UTF-8
        //Transfer-Encoding: chunked
        //Connection: keep-alive
        //X-Apple-I-Request-ID: 81f02897-3c1e-11ef-88f9-613fbdd33a72
        //X-FRAME-OPTIONS: DENY
        //X-Content-Type-Options: nosniff
        //X-XSS-Protection: 1; mode=block
        //Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
        //Content-Security-Policy: default-src 'self' ; child-src blob: ; connect-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://webcourier.sandbox.push.apple.com https://xp-qa.apple.com ; font-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ; frame-src 'self' https://appleid.apple.com https://gsa.apple.com ; img-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://*.mzstatic.com data: https://*.apple.com ; media-src data: ; object-src 'none' ; script-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ; style-src 'unsafe-inline' 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ;
        //Referrer-Policy: origin
        //X-BuildVersion: R12
        //scnt: AAAA-jJCQUNBRTc3MkQ3NzdGQjYzRjM4QkE3NDQ1NjREMzQyNkIxMkY0NzYyQjhFNzFENjFCNDMzNjE1ODJGQkI3NkM4RTczQzJFNzBBNzgyRjQ5MzAxQjdGMkRGRjk4RjU4RDI1RkM5QjVBQjlCNjMwRTBBQzIyQjhDMzhENjAwNjk1NjI2RkFENEI1OTNFODE0MUVEQTM4OEJGMzI5NTVEMDg5MDVFRjc2NDg0MzM2NkU2NzAzM0Y2NzZEQzBBMjBGRjIwNTI0Nzg3OUQ2QUUzNkY5OEI5NjdFN0NCNDdFMzg3MUEyN0RDMDQ0MDhCRUJDNXw0AAABkIuiyY-nvfKxWALqkV3Vr4Qd_D1gdCRCiDG4P-0_Dx93Q-5-EJ023mc2LODDAByzCB8LIfAKO3Io2Nd4YX-9WJOZGMAo0e1i03lX1fPKE7PJ9IAfFQ
        //Set-Cookie: dslang=CN-ZH; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: site=CHN; Domain=apple.com; Path=/; Secure; HttpOnly
        //Pragma: no-cache
        //Expires: Thu, 01 Jan 1970 00:00:00 GMT
        //Cache-Control: no-cache
        //Cache-Control: no-store
        //X-Apple-ID-Session-Id: 2BACAE772D777FB63F38BA744564D3426B12F4762B8E71D61B43361582FBB76C8E73C2E70A782F49301B7F2DFF98F58D25FC9B5AB9B630E0AC22B8C38D600695626FAD4B593E8141EDA388BF32955D08905EF764843366E67033F676DC0A20FF205247879D6AE36F98B967E7CB47E3871A27DC04408BEBC5
        //X-Apple-Auth-Attributes: oYf/kb9V6r7YWZwFyzr0vKY5qrZG8iChLvMvZCbR7LCEH5OSMoPyrC7GNC1c70hj61nDGNdlToCXaMKSNEgpnhu+0XwL+TbNk4uqiMug7ao3xo39oFoQ4PmAdU4jcZqI56wFXHoH+9Jn3iFoAMRKZMQk4dcWn9zR/nfwuiIjv/YsxwW88EIzCp9dvPDGqdypDlU3G57ZLW+7yQAcswgk9DKy
        //X-Apple-ID-Account-Country: USA
        //Cache-Control: no-store
        //vary: accept-encoding
        //Content-Language: zh-CN-x-lvariant-CHN
        //
        //80b
        //{
        //  "trustedDeviceCount" : 3,
        //  "securityCode" : {
        //    "length" : 6,
        //    "tooManyCodesSent" : false,
        //    "tooManyCodesValidated" : false,
        //    "securityCodeLocked" : false,
        //    "securityCodeCooldown" : false
        //  },
        //  "phoneNumberVerification" : {
        //    "trustedPhoneNumbers" : [ {
        //      "numberWithDialCode" : "+86 ••• •••• ••21",
        //      "pushMode" : "sms",
        //      "obfuscatedNumber" : "••• •••• ••21",
        //      "lastTwoDigits" : "21",
        //      "id" : 1
        //    } ],
        //    "securityCode" : {
        //      "length" : 6,
        //      "tooManyCodesSent" : false,
        //      "tooManyCodesValidated" : false,
        //      "securityCodeLocked" : false,
        //      "securityCodeCooldown" : false
        //    },
        //    "authenticationType" : "hsa2",
        //    "recoveryUrl" : "https://iforgot.apple.com/phone/add?prs_account_nm=jackchang2021%40163.com&autoSubmitAccount=true&appId=142",
        //    "cantUsePhoneNumberUrl" : "https://iforgot.apple.com/iforgot/phone/add?context=cantuse&prs_account_nm=jackchang2021%40163.com&autoSubmitAccount=true&appId=142",
        //    "recoveryWebUrl" : "https://iforgot.apple.com/password/verify/appleid?prs_account_nm=jackchang2021%40163.com&autoSubmitAccount=true&appId=142",
        //    "repairPhoneNumberUrl" : "https://gsa.apple.com/appleid/account/manage/repair/verify/phone",
        //    "repairPhoneNumberWebUrl" : "https://appleid.apple.com/widget/account/repair?#!repair",
        //    "aboutTwoFactorAuthenticationUrl" : "https://support.apple.com/kb/HT204921",
        //    "autoVerified" : false,
        //    "showAutoVerificationUI" : false,
        //    "supportsCustodianRecovery" : false,
        //    "hideSendSMSCodeOption" : false,
        //    "supervisedChangePasswordFlow" : false,
        //    "trustedPhoneNumber" : {
        //      "numberWithDialCode" : "+86 ••• •••• ••21",
        //      "pushMode" : "sms",
        //      "obfuscatedNumber" : "••• •••• ••21",
        //      "lastTwoDigits" : "21",
        //      "id" : 1
        //    },
        //    "hsa2Account" : true,
        //    "restrictedAccount" : false,
        //    "supportsRecovery" : true,
        //    "managedAccount" : false
        //  },
        //  "aboutTwoFactorAuthenticationUrl" : "https://support.apple.com/kb/HT204921"
        //}
        //0

    }

    /**
     * 验证安全代码(邮箱验证码)
     * @param string $code
     * @return Response
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function validateSecurityCode(string $code): Response
    {
        //idclient=web; dslang=CN-ZH; site=CHN;
        // aidsp=EB2D35D1537073828EE5AF25B790CBBBD0B4D3BAB40E879F78709BC4C7927C57C2136C84C1D8C1DF513D8AFC70C0AADEDC09A44C7513577864E996CA31FE2645F62640FE06BEE12515C863358BCB5594FF4561B718B895148242839DB7722DD3089FD1D85ADA9E6AC77F41068AA8EA0BA35128C99AAA7213;
        // aasp=039B5318B368AD303E8A0F3BDC9B821E2B12500D4A36BF240892CBCE86332814A05C6160CF76EEE3CBB17D5E805CCE973B5E3CF20EC535F7207FB4633612DCA147EA7311127B30AD000581DF0F5C65A721454ED20420676B43162FFFDA957B259B431FE407FAF9560F10379F2B22ADF6F2F44F8FCE7BC74B;
        // acn01=ZXlaVXOLMJyGuczjxHxW1z6ICJ/fl8NoP/uvzA4AOEEnjQBi9w==

        //POST https://idmsa.apple.com/appleauth/auth/verify/trusteddevice/securitycode HTTP/1.1
        //POST https://idmsa.apple.com/appleauth/auth/verify/trusteddevice/securitycode HTTP/1.1
        //Connection: Keep-Alive
        //Content-Type: application/json; Charset=UTF-8
        //Accept: application/json
        //Accept-Language: zh-CN,zh;q=0.9
        //Cookie: idclient=web; dslang=CN-ZH; site=CHN; aidsp=84DD7C8C791B835CA5846EB103C4DE742C98EF7432E711ED944642E9296904CD1CE55B5AED98F146ECA6D97DFAFB9B065887B042F5289A7D665B15FB38683D23951B18737BB84E65E377B8B7CDB6F6C016B1F13D19C647385721563CF04B887280953351B87F5C6DBF95B24F89E719DEC1ABB25035329140; aasp=93A2C2892AC15C46F64E58BDD8E95AB95C2FCBAFCF3C8488F45EAF2720ABC76AE2E20B28A0722C1AB5177171A6FD985D1EEA22F2FA86A28EC697306139C009E0F6CB0A9ACE170C6C2900303AB3A1092E3E97DD7B1B3404D3FFA73093F68A85FE5BDC2844CBE96E8806380E60E930E7A528BA576F94970BE8; acn01=bLisnWKDdII6oSBiZHswKLqahoy8Hz937Yslo68AN1GhmOuAfw==
        //Host: idmsa.apple.com
        //Referer: https://idmsa.apple.com/
        //User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36
        //scnt: AAAA-jkzQTJDMjg5MkFDMTVDNDZGNjRFNThCREQ4RTk1QUI5NUMyRkNCQUZDRjNDODQ4OEY0NUVBRjI3MjBBQkM3NkFFMkUyMEIyOEEwNzIyQzFBQjUxNzcxNzFBNkZEOTg1RDFFRUEyMkYyRkE4NkEyOEVDNjk3MzA2MTM5QzAwOUUwRjZDQjBBOUFDRTE3MEM2QzI5MDAzMDNBQjNBMTA5MkUzRTk3REQ3QjFCMzQwNEQzRkZBNzMwOTNGNjhBODVGRTVCREMyODQ0Q0JFOTZFODgwNjM4MEU2MEU5MzBFN0E1MjhCQTU3NkY5NDk3MEJFOHwxAAABkIuT_Xg1QeUK0Kd9V9I_xEYHhMvHYzC0AA_n_B7y1ftyLoEsUy1SkdlRuvKpADdRoYqCYv1RDoSQiRSbIb_8NcqY3i2j0iFTEnvWFcCd5JUb7sANag
        //X-Apple-Widget-Key: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Apple-OAuth-Redirect-URI: https://appleid.apple.com
        //X-Apple-OAuth-Client-Id: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Apple-OAuth-Client-Type: firstPartyAuth
        //x-requested-with: XMLHttpRequest
        //X-Apple-ID-Session-Id: 93A2C2892AC15C46F64E58BDD8E95AB95C2FCBAFCF3C8488F45EAF2720ABC76AE2E20B28A0722C1AB5177171A6FD985D1EEA22F2FA86A28EC697306139C009E0F6CB0A9ACE170C6C2900303AB3A1092E3E97DD7B1B3404D3FFA73093F68A85FE5BDC2844CBE96E8806380E60E930E7A528BA576F94970BE8
        //X-Apple-Auth-Attributes: DeIxWAo5tbyBK2E3U8Fs3o4N3azDOXeZ8XJ0COQmYju9CMoS9x2O+eQwBkokCQDnmmfXwYHlgb6QnVf29ewVFMCMTm8QHm4S1XVsDiJ2Tv+AJW/ScbL0HZ0ZbZ+nywluGVKTtEnAh2ju4+bhZvRxdQSt7K34aFL01+PoIiCcAN1LkcgK69g/XUUjG/CtDZgeoKkLyCuqL69jqwA3UaGY+/ro
        //X-Apple-OAuth-Response-Type: code
        //X-Apple-OAuth-Response-Mode: web_message
        //X-Apple-Domain-Id: 1
        //Origin: https://idmsa.apple.com
        //Sec-Fetch-Site: same-origin
        //Sec-Fetch-Mode: cors
        //Sec-Fetch-Dest: empty
        //Content-Length: 34
        //
        //{"securityCode":{"code":"222222"}}

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
        ]);
    }

    /**
     * 发送手机验证码
     * @param int $id
     * @return Response
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function sendPhoneSecurityCode(int $id): Response
    {
        //PUT https://idmsa.apple.com/appleauth/auth/verify/phone HTTP/1.1
        //Connection: Keep-Alive
        //Content-Type: application/json; Charset=UTF-8
        //Accept: application/json
        //Accept-Language: zh-CN,zh;q=0.9
        //Cookie: idclient=web; dslang=CN-ZH; site=CHN; aidsp=84DD7C8C791B835CA5846EB103C4DE742C98EF7432E711ED944642E9296904CD1CE55B5AED98F146ECA6D97DFAFB9B065887B042F5289A7D665B15FB38683D23951B18737BB84E65E377B8B7CDB6F6C016B1F13D19C647385721563CF04B887280953351B87F5C6DBF95B24F89E719DEC1ABB25035329140; aasp=93A2C2892AC15C46F64E58BDD8E95AB95C2FCBAFCF3C8488F45EAF2720ABC76AE2E20B28A0722C1AB5177171A6FD985D1EEA22F2FA86A28EC697306139C009E0F6CB0A9ACE170C6C2900303AB3A1092E3E97DD7B1B3404D3FFA73093F68A85FE5BDC2844CBE96E8806380E60E930E7A528BA576F94970BE8; acn01=bLisnWKDdII6oSBiZHswKLqahoy8Hz937Yslo68AN1GhmOuAfw==
        //Host: idmsa.apple.com
        //Referer: https://idmsa.apple.com/
        //User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36
        //scnt: AAAA-jkzQTJDMjg5MkFDMTVDNDZGNjRFNThCREQ4RTk1QUI5NUMyRkNCQUZDRjNDODQ4OEY0NUVBRjI3MjBBQkM3NkFFMkUyMEIyOEEwNzIyQzFBQjUxNzcxNzFBNkZEOTg1RDFFRUEyMkYyRkE4NkEyOEVDNjk3MzA2MTM5QzAwOUUwRjZDQjBBOUFDRTE3MEM2QzI5MDAzMDNBQjNBMTA5MkUzRTk3REQ3QjFCMzQwNEQzRkZBNzMwOTNGNjhBODVGRTVCREMyODQ0Q0JFOTZFODgwNjM4MEU2MEU5MzBFN0E1MjhCQTU3NkY5NDk3MEJFOHwxAAABkIuT_Xg1QeUK0Kd9V9I_xEYHhMvHYzC0AA_n_B7y1ftyLoEsUy1SkdlRuvKpADdRoYqCYv1RDoSQiRSbIb_8NcqY3i2j0iFTEnvWFcCd5JUb7sANag
        //X-Apple-Widget-Key: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Apple-OAuth-Redirect-URI: https://appleid.apple.com
        //X-Apple-OAuth-Client-Id: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Apple-OAuth-Client-Type: firstPartyAuth
        //x-requested-with: XMLHttpRequest
        //X-Apple-ID-Session-Id: 93A2C2892AC15C46F64E58BDD8E95AB95C2FCBAFCF3C8488F45EAF2720ABC76AE2E20B28A0722C1AB5177171A6FD985D1EEA22F2FA86A28EC697306139C009E0F6CB0A9ACE170C6C2900303AB3A1092E3E97DD7B1B3404D3FFA73093F68A85FE5BDC2844CBE96E8806380E60E930E7A528BA576F94970BE8
        //X-Apple-Auth-Attributes: DeIxWAo5tbyBK2E3U8Fs3o4N3azDOXeZ8XJ0COQmYju9CMoS9x2O+eQwBkokCQDnmmfXwYHlgb6QnVf29ewVFMCMTm8QHm4S1XVsDiJ2Tv+AJW/ScbL0HZ0ZbZ+nywluGVKTtEnAh2ju4+bhZvRxdQSt7K34aFL01+PoIiCcAN1LkcgK69g/XUUjG/CtDZgeoKkLyCuqL69jqwA3UaGY+/ro
        //X-Apple-OAuth-Response-Type: code
        //X-Apple-OAuth-Response-Mode: web_message
        //X-Apple-Domain-Id: 1
        //Origin: https://idmsa.apple.com
        //Sec-Fetch-Site: same-origin
        //Sec-Fetch-Mode: cors
        //Sec-Fetch-Dest: empty
        //Content-Length: 37

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
        //POST https://appleid.apple.com/account/manage/security/verify/phone/securitycode HTTP/2
        //host: appleid.apple.com
        //content-length: 165
        //sec-ch-ua: "Not/A)Brand";v="8", "Chromium";v="126", "Google Chrome";v="126"
        //scnt: AAAA-kU4RjQ2NkY3RUE5M0E2MzdFQzUwRkY4MjIwNjdEMDkzQjBBRjEzRTc3ODRBRkI3Q0YxODMwMjY2NzIyMzc3MUNDNUYwRjA3RDhBN0IzRDBCMTkwNTE4MTlCMzQ0MkM3QUJDRDNFQTk2QUY4QTE0MEY1RTc1M0VDNTAyNjc1NzJFMjVFNTUxQjJEN0M1ODZCNzk0NjAzODA3OTM0NzY5OTgwRUE2OTE5RERBNjY3NkJCNEQ4Qzk0Q0U0QjJDOTg2NzBDNjZBMTNEQjVGQTE5NzkzMDYyNTQ1MjJBQjk3MzY2NEE3MEVCNzc3M0M4NDY2M3w5AAABkLqkxehPDrQnDHZGyhWKo0G8c2UOctvI-Ie5WJzCXhfMgh2jpzluTbA3dXfqAB0ToaQi3yM77aJFhQFaRcChyKtI4OaPjXbL7Ya5tZEUiqYtqjZgXg
        //x-apple-i-fd-client-info: {"U":"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36","L":"zh-CN","Z":"GMT+08:00","V":"1.1","F":"Fla44j1e3NlY5BNlY5BSmHACVZXnNA9cdHmduW_2AUfSHolk2dUJKy_Aw7GY5Ly.EKY.6eke4FIdIXVwc6wEOy37lY5BNleBBNlYCa1nkBMfs.4h."}
        //x-apple-i-request-context: ca
        //sec-ch-ua-mobile: ?0
        //user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36
        //content-type: application/json
        //accept: application/json, text/plain, */*
        //x-apple-i-timezone: Asia/Shanghai
        //x-apple-api-key: cbf64fd6843ee630b463f358ea0b707b
        //sec-ch-ua-platform: "Windows"
        //origin: https://appleid.apple.com
        //sec-fetch-site: same-origin
        //sec-fetch-mode: cors
        //sec-fetch-dest: empty
        //referer: https://appleid.apple.com/
        //accept-encoding: gzip, deflate, br, zstd
        //accept-language: zh-CN,zh;q=0.9
        //priority: u=1, i
        //cookie: idclient=web; dslang=CN-ZH; site=CHN; geo=CN; myacinfo=DAWTKNV323952cf8084a204fb20ab2508441a07d02d3e3496fea35cabd9d853b2e6f61d028f07333fe125458baa0e599367f7a72684d0ca1d836468fdc8a1314cdf737707c4da12383289215f6885bf72abf7b5ed4db40b1bb73b64556820fc1e2e31802d7eccd327a9d7de0785ba88cc946abd684618cbe3c60ebe65eb4e8e503c41f230ed0362a0eacc1c20cafa02e1361dabd0423e3a55198eadd1a810b7c25ac84089cbde15c77d68a41d5b6a35cb3b1bb62d52ee5d7a0ad6a8e010f517b20f211d0756a333f503e15d3eceaa775039b0975e617c1a0fb9f03fd37caee5e4af4b462fee7adb9f0d5851a0bf3ebe9a86dc1d6a55bd2eb57a7b411c7b4075be56badaf49473e94b17954f2ff1a551a5422f45eafbb48d7f5dc00384dc3c40bc0dfd17a67ca7f8d7c378f5278d5f0361298449ffe5d93d6bbaad5952570cbed9865d1c7bf95f2d057026292c3edbf1adfcf565789922916f04749601537d95a682603373a00170d1232b1acb15ae55cc23f1afc0f58b36198326e43b64e95be37cc33f10273efe19c7554d05167b6bfdc4094de71ae98ed7fe9ff43fd859a8450c4154f9dfd184087fc0fedd78e328ad2981d3296a281e7c4362f5362433fca6e542ccd158048ae460e2f5f24103730ca98c5c2ad26398cb80789527c3f2ddb49e3b5bdb505568f3084d307f4b25ef1d8a488b33f6f16caf53afe91787da18df0e8c91d10a342753c2557f84f4922f368289b8209f84ce65e8025ad58b77e055472530a4bdf222415e8b00d9da06fd466794a541fcecadaaa7df128570ef3a8ea266d55db4661f11c5f021ef21845ad29bccc6d9beec62a327107dc7bf316f6677f186737511988a1cba355b72214898465de79b931a37556a6783835ecf591b98124355ca441af30fe1d621c7cc19d02906ddf0b3620e614d8e91d369d2d5b03d823e01ddcab41bdf0c168dc9f175cba2dcaa6f017585a47V3; aidsp=E8F466F7EA93A637EC50FF822067D093B0AF13E7784AFB7CF18302667223771CC5F0F07D8A7B3D0B19051819B3442C7ABCD3EA96AF8A140F5E753EC50267572E25E551B2D7C586B794603807934769980EA6919DDA6676BB4D8C94CE4B2C98670C66A13DB5FA1979306254522AB973664A70EB7773C84663; dat=XHBKTyBShD8eQ/n6WBglHvitk62TlLylK6eFrWvDJAw52WPZpreqFBhwM2sW/gOZBFH59CvWOfOUSwT1+z6vO+vfCk73EU8PRP6sjDJpW8vL6IbmeJub96/4b9fIiDbBiK44L0qiYezaEH2aRpWxB86Q2SUXgb2++lfOb1D1M/70xTin/lQ2/HGEFGdrRyC+uo58SrDgRoABU4q05fvF3qTtPdqx1wFgi3gTdFeO7tbx7MH/qJKXX5uKCRBsmulTDUSNf94NEkqu3WnUVhiZUw722Lj1FXxIVyCDFkVItmltUjs0O4tSSyisSclV1OTjx3/yaHAJ4hvyOQs/HQ7BSTeNWWPLdZhcVl1d9FxMbMHyiVWI0LwAH3B+JMdz7Q==; itspod=51; aid=85207E40D15815FC429A189E6C836AD0; crsc=0IbnScgfxG349r860FmSkB3hqHLGfIoTArMrMnmEfrmcIAt2IejTrudpNQ+oLwalXrbBJI+jMoVMU/U1vFP2V9oAHROhwOsE7Q==; caw-at=eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ.eyJzdWIiOiIwMDE1ODItMTAtZDAyZDc3ZGUtZDFmZS00M2QyLWFlYjItM2Y3MWU5YTE2OWM3IiwibHZwY3RzIjoxNzIxMTE3MzY3ODIzLCJpc3MiOiJjb20uYXBwbGUuaWRtcy5jbGllbnQiLCJleHAiOjE3MjExMTgyNjksImlhdCI6MTcyMTExNzM2OX0.-WwY_r4EjvVmXrxEgy1z6oAosd_GSQNsG7pnKmOgAJXVd2A43V7R9Ot33pl1lqROAlkb68wRz69cy_zuZzRZ0A; awat=TURBeE5UZ3lMVEV3TFdRd01tUTNOMlJsTFdReFptVXRORE5rTWkxaFpXSXlMVE5tTnpGbE9XRXhOamxqTnpveE56SXhNVEUzTXpZNU5qTXdPalk9OjUwbzlNck1LTm9UL0VvVUZyM09nZUVaRzNPQkMyOEljMk9CSE9xK0lnWkZtUW9tUDQ3ejlBSHZtQWVaZWZCM2Y5QWswNThKSjZpazd4Vm1NUGxpcXV1MWlESkZ4cjJuSHRYZDlXMG5EOXVLN2JlaEZPZVZsUTRlQ0dYaUZiUU9HSXRtbjNRNndpTHNnNmxaZjFteEFSbDk3dlVpSnVpQktHRS9TT3dwM2ltRjdNSXF1WVBNeGlCa2lta3orN2NBWnBLQlZVT3FHYTFOK1hZQ0lDNVlma2lkRjhjc3hwd3ZqalRBU2RZbC81alRRcmJ5Q0dlVW5NSGE2UGpsRmdwNGVDT2R6Zk5paWVTYUFmVUNnUFlDYzJWalFWUXY5TEo0bVRyem9raGVhZTJSVHZ2S2FsNFBYdHlmcEkxZW5iV3BTUitpZTVZcEdtWC8yNlQ5VTJTZk4zN3NDRUYxSUlib2pIbWxpaUd6K0RCTm52Y2xiSkd3bUhBVE50N2d1TzdtVFF3OHRkZ0xHVnhJYmQvbExMaWdVZmIraUdHRDBna29Zcy9uQnVVZ0FYcEROaVZqY1NzTWVOajRkNGNqdGRhSGpVemJZTHdzWElLdzBTT24vT1krRkVTYjV1bHZuVEV4SGd3TDlvRjN1YWVnYzZ6cnRlM1FDUitRenZyMjJ1bTJDZzVsREc2RkdVYjNiWWJKNVlnb0IrMS9JMHJwbkg1NVJuV3VXd010TXhENlpNeEoxZUk3THQ2RGo1d3M1emVoRS8zcHFTSVczdzB0Ykl6RFRXczM5QUd6SXM2aUMraUg2SFpWckVvZ1BmemxaRlE3VUVWSHBaL2xyeEtGNkhJTWxxRS9DcjFJS0VDeU5uNjVlRzg5RG1WeUZLeFBuOHY3OVRISHB4MkV1NU5PbVNYSU55K0lyMFpDdHIwbVU5TkxES2IrdFV3UDJFN28wN1JhUnZOdlJUeFJLTzRQMldreTRjQUlEdGNSa2o0UkVxcXdtUFI5ckxJTUxrNVhnQkJmN1pZUXFIQ2wrVmRpblo2M0Q0SWpJTWcvYWtGdnAwMjdsTWV4d1FnQ2F2Ykt6cGZDZXdmajNrTmM1YldPcmJWdmFHV3FudWhmSnozS1NxU1hNU2FhdHhoSmVCckxkWnk4NjNPY3VpNjAvWXIxU3dQcUpMaXhLclF2UmFMQVdqNWlXSU5YQzhqdFF5VzdlUUd2RktjQ3ZLc3VRUis4NHdVWkxSRFR3WHFqTTRmbjdtVWF5d3FnbUxjRm9zdzE0RG5wU0Z3VWZBdE5HOEdvM0lqQ003ZHEwYjF5bUdmeGRrWGpaT2l5dmczcEQrM3ZrVCtuZThiMVhoYjZSRkMwUnc2NW9KK0gwdXpEMms5QlRXZmJKT2p6aHkvK21yQW1ZZTNLLzlWTm1jRWk1V2NOYkl0aU93Tmd2eUkzU01hTVRPbmhNRlFBZEU2SEJGRWc2fG9zTVBRTElLcGRZM3pqWDlZVTBtQm5WSHg5OD0=
        //
        //{"phoneNumberVerification":{"phoneNumber":{"id":20101,"number":"63303506","countryCode":"HK","countryDialCode":"852"},"securityCode":{"code":"234568"},"mode":"sms"}}

        return $this->appleidRequest('POST', '/account/manage/security/verify/phone/securitycode', [
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


        //HTTP/1.1 400
        //Server: Apple
        //Date: Tue, 16 Jul 2024 08:09:44 GMT
        //Content-Type: application/json;charset=UTF-8
        //X-Apple-I-Request-ID: c2cc3ba6-434a-11ef-b9b8-d579d9666273
        //X-BuildVersion: R13
        //X-FRAME-OPTIONS: DENY
        //X-Content-Type-Options: nosniff
        //X-XSS-Protection: 1; mode=block
        //Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
        //Content-Security-Policy: default-src 'self' ; connect-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com https://api.apple-cloudkit.com https://feedbackws.apple-cloudkit.com https://*.icloud-content.com https://*.icloud-content.com.cn ; font-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com ; frame-src 'self' https://idmsa.apple.com https://signin.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://familyws.icloud.apple.com  https://apps.apple.com ; img-src 'self' https://www.apple.com https://appleid.cdn-apple.com data: https://*.mzstatic.com https://appleid.apple.com https://*.icloud.com ; media-src data: ; object-src 'none' ; script-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://signin.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://appleid.apple.com ; style-src 'unsafe-inline' 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com ;
        //Referrer-Policy: origin
        //scnt: AAAA-0U4RjQ2NkY3RUE5M0E2MzdFQzUwRkY4MjIwNjdEMDkzQjBBRjEzRTc3ODRBRkI3Q0YxODMwMjY2NzIyMzc3MUNDNUYwRjA3RDhBN0IzRDBCMTkwNTE4MTlCMzQ0MkM3QUJDRDNFQTk2QUY4QTE0MEY1RTc1M0VDNTAyNjc1NzJFMjVFNTUxQjJEN0M1ODZCNzk0NjAzODA3OTM0NzY5OTgwRUE2OTE5RERBNjY3NkJCNEQ4Qzk0Q0U0QjJDOTg2NzBDNjZBMTNEQjVGQTE5NzkzMDYyNTQ1MjJBQjk3MzY2NEE3MEVCNzc3M0M4NDY2M3wxMAAAAZC6pQKK1jJeV9QL8up9Mt2SQmRttsMX4qEE3GXm2D3d1F27IlCm58iPKCB4nwAfyDxlbBWWcloKmFLsa-Boj7t-4qwOAI6T1a6r3yHaSTz6FL6jeqI
        //Set-Cookie: dslang=CN-ZH; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: site=CHN; Domain=apple.com; Path=/; Secure; HttpOnly
        //Pragma: no-cache
        //Expires: Thu, 01 Jan 1970 00:00:00 GMT
        //Cache-Control: no-cache
        //Cache-Control: no-store
        //X-Apple-I-Ercd: -21669
        //Set-Cookie: awat=TURBeE5UZ3lMVEV3TFdRd01tUTNOMlJsTFdReFptVXRORE5rTWkxaFpXSXlMVE5tTnpGbE9XRXhOamxqTnpveE56SXhNVEUzTXpnME9ESTFPamM9OlhvNE9zNW8wQkE5MG5laUhLekdYMWh1V1JUa1ZaVnRCZDUrcnRnanRCRUV1bkRPLzdYMVZGd2lFNGdFQkNrUjQxV3lpK25ka3czOWdhNDROdmxSdlJHc3dFazIrOHJJUXFWYXJQK2ZKSWlpQkZLUndyWmZWWHBSM1IwQlhFUWU4a2RVUDhCTGkyaU1IZWwrZXE0eU41bjBpck9BNE13VkF4VUJkWnZGZ3JZMHovVWFkV0FOM3gwRnlpOU5CbFVEeHpnZlJ6NWVRdGE5TkYyYi9VSXY5Q1VCOHM2amJoRzRqelRIS2lkem5lRUFjVUJZRnFhZ0RNQ3VBV2FBeGZ3WU1mREd4S0ppanRKNVpzWEF0cms1cjAycjlDd040YXE4VytRbVNFcUFnTk45NERhU3dsYmQxYkR6ZS82R25ycjFhUVlvYnVIMjNkakpHVWlwT2pTMmlkeUcwZWQ2bWgrbGZrWTZIMTJJRTl3V2p5Z3N1dXptMFFXcitEaFJ2SXJJUy92QkxNd29Cbm1mRnNlMkhMa3lTeVY4dFFTVDV1bmNnT1JhQS82cTk0WDdiVGgvY1owS3RqclJKZjE1SUVUN2ZrRlYxUE0wRDVLSXdVTXE1QjM5Wi9MaW5kc0FHUWhMY2p4UEhKSm9rR3JuRzExUEdNMDhhbDZIV1VmYUhPdTRvcTVKdmRXQmxpem16R0doMC84cWJVRWtlaXJDM285NUVXUndzQlJNTWlIWGJ3R00wNy9RdmZkWWZhQjVySlhGQnJoWDhuUkVjbkh2YS9KUFViS0lGaXU4YXlWSzNaVkcrWTZ5dEUxYkltS0ZxZmVHNEx0T0crZEgrSTR1UnZYYmxNeGgyRkk5M2F4ZVE4NDNLdW1GQnkreFVlaDJ6amVaUTF5TjYyc3o1L1NyV09QNEwyRFFOTE9xYUlKcmRaemRiTDNQNnRPUEorMko2K3A5dVVwYUZzZUduMHRuS0sxYWhjOFdpa3F1Zlc3aXlHNmdlUHJvSXFvMmdtVlpYNGRVNDFXNTlreXBvWW9SYVdzWU0rRFVZMTJSNWtUU1Uwc2FmVklrYmpLeWdsUkpudnl0MDcwMW8xMUZDVTFtTEdISThDd1FHQmVBUVJOa2dHOVh0Vkx1dEZqUWkzWG5LL3RYcjB6M3BIME1HYmVjc0lwMWpSWUdYZllBZDhpVktVVVFhUGVIV1hrdUdNRjZyZzIzL29maFdsRWhGb3JRcHdXZzNFYXhiV0N3TWc4QS9USS9hNDVpMTlzN0I5MWlaVDYrOW00cWZad1VRRm1TcFBrclo0ci9DR0x5THZRanlzQmh3N2RwQy9PZVpIYTVid2c2NldJTEJCaUhwNnlWcUcxMzNHN3Y5MllVTFNNS0hDZW5JMTkvYTdaOUNzaU5MV3ZwU2NFVTVSb0RiV05YNEZhMnRTNHhHa0Rja0hlTkRWb0pjeGdBZnlEeHU1eDZOfFp5Wk0zQjZiMWZ5by9nRXNMUkJYMG1QNWlCbz0=; Max-Age=900; Expires=Tue, 16 Jul 2024 08:24:44 GMT; Domain=appleid.apple.com; Path=/; Secure; HttpOnly
        //X-Apple-I-Request-Context: ca
        //Cache-Control: no-store
        //vary: accept-encoding
        //Content-Encoding: gzip
        //Content-Language: zh-CN-x-lvariant-CHN
        //Content-Length: 158
        //
        //{
        //  "service_errors" : [ {
        //    "code" : "-21669",
        //    "title" : "验证码不正确",
        //    "message" : "验证码不正确。",
        //    "suppressDismissal" : false
        //  } ],
        //  "hasError" : true
        //}
    }
}
