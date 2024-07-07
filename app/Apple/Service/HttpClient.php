<?php

namespace App\Apple\Service;

use App\Apple\Service\Exception\UnauthorizedException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpFoundation\Cookie;

class HttpClient
{

    protected ?Client $client = null;

    protected ?Config $config = null;

    protected CookieJarInterface $cookieJar;

    protected array $header = [];

    //401 Unauthorized

//    protected LoggerInterface $logger;

    public function __construct(
        protected ClientFactory $clientFactory,
        protected CookieManagerFactory $cookieManagerFactory,
        protected LoggerInterface $logger,
        protected CacheInterface $cache,
        protected string $clientId,
    )
    {
        $this->cookieJar = $this->cookieManagerFactory->create($clientId);
        $this->header = $this->loadHead();
    }

    /**
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function loadHead(): mixed
    {
        return $this->cache->get($this->clientId) ?? [];
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    /**
     * @param string $username
     * @param string $password
     * @return Response
     * @throws UnauthorizedException
     * @throws \GuzzleHttp\Exception\GuzzleException|\Psr\SimpleCache\InvalidArgumentException
     */
    public function signin(string $username, string $password): Response
    {
        $this->bootstrap();

        $this->authAuthorizeSignin();

        $response = $this->authSignin($username, $password);

        if (!empty($scnt = $response->getResponse()->getHeader('scnt'))){
            $this->header['scnt'] = $scnt;
            $this->cache->set($this->clientId, $this->header);
        }

        $authResponse = $this->auth();
        $phoneInfo = $authResponse->getData();
        $this->config->setPhoneInfo($phoneInfo);

        return $response;
    }


    /**
     * @return Client
     * @throws \GuzzleHttp\Exception\GuzzleException|UnauthorizedException
     */
    public function getClient(): Client
    {
        if ($this->client === null) {

            $this->client = $this->clientFactory->create($this->getConfig(),[
                'cookies' => $this->cookieJar,
            ]);
        }
        return $this->client;
    }

    /**
     * @return Config
     * @throws UnauthorizedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getConfig(): Config
    {
        if ($this->config === null) {
            //获取配置
            $response = $this->bootstrap();
            if (empty($data = $response->getData())){
                throw new UnauthorizedException('未获取到配置信息');
            }

            $this->createConfig($data);
        }

        return $this->config;
    }

    public function createConfig(array $config = []): Config
    {
        return $this->config = new Config(
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
     * @throws \GuzzleHttp\Exception\GuzzleException|UnauthorizedException
     */
    public function request (string $method, string $uri, array $options = []): Response
    {

        $aaspValue = $this->cookieJar->getCookieByName('aasp')?->getValue();
        if ($aaspValue !== null) {
            $this->logger->info("aasp cookie is $aaspValue");
        }

        $defaultOptions = [
            RequestOptions::HEADERS => array_merge($this->header, [
                'X-Apple-ID-Session-Id' => $aaspValue,
            ]),
        ];

        $response = $this->getClient()->request($method, $uri, array_merge($defaultOptions, $options, $this->header));


        return $this->parseJsonResponse($response);
    }

    //解析 返回数据
    public function parseJsonResponse(ResponseInterface $response): Response
    {
        return new Response(
            response: $response,
            status: $response->getStatusCode(),
            data: json_decode((string)$response->getBody(), true) ?? []
        );
    }

    public function buildUUid(): string
    {
        return sprintf('auth-%s',uniqid());
    }


    /**
     * 获取 bootstrap
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException
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
        $client = new Client([
            RequestOptions::COOKIES => $this->cookieJar,
        ]);

        $response = $client->get('https://appleid.apple.com/bootstrap/portal');
        return $this->parseJsonResponse($response);

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
     * @throws \GuzzleHttp\Exception\GuzzleException
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

        $response = $this->request('GET', '/appleauth/auth/authorize/signin',[
            RequestOptions::QUERY => [
                'frame_id' => $this->buildUUid(),
                'skVersion' => '7',
                'iframeId' => $this->buildUUid(),
                'client_id' => $this->getConfig()->getServiceKey(),
                'redirect_uri' => $this->getConfig()->getApiUrl(),
                'response_type' => 'code',
                'response_mode' => 'web_message',
                'state' => $this->buildUUid(),
                'authVersion' => 'latest',
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
     * 授权登录
     * @param string $accountName
     * @param string $password
     * @param bool $rememberMe
     * @return Response
     * @throws UnauthorizedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function authSignin(string $accountName, string $password, bool $rememberMe = true): Response
    {
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

        $response = $this->request('post','/appleauth/auth/signin?isRememberMeEnabled=true',[
            RequestOptions::JSON => [
                'accountName'=>$accountName,
                'password'=>$password,
                'rememberMe'=> $rememberMe,
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
    }


    /**
     * 双重认证首页
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function auth (): Response
    {
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

        return $this->request('GET', '/appleauth/auth');

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
    }


    /**
     * 验证手机验证码
     * @param int $id
     * @param string $code
     * @return Response
     * @throws UnauthorizedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function authVerifyPhoneSecurityCode(string $code,int $id = 1):Response
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

        $response = $this->request('post','/appleauth/auth/verify/phone/securitycode', [
            RequestOptions::JSON=>[
                'phoneNumber'=>[
                    'id'=>$id,
                ],
                'securityCode'=>[
                    'code'=>$code,
                ],
                'mode'=>'sms',
            ],
        ]);

        if ($response->getStatus() !== 200) {
            throw new UnauthorizedException($response->getFirstErrorMessage(),$response->getStatus());
        }

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
        //{
        //  "trustedPhoneNumbers" : [ {
        //    "numberWithDialCode" : "+86 ••• •••• ••70",
        //    "pushMode" : "sms",
        //    "obfuscatedNumber" : "••• •••• ••70",
        //    "lastTwoDigits" : "70",
        //    "id" : 1
        //  } ],
        //  "phoneNumber" : {
        //    "numberWithDialCode" : "+86 177 5246 3370",
        //    "pushMode" : "sms",
        //    "obfuscatedNumber" : "•••••••••70",
        //    "lastTwoDigits" : "70",
        //    "id" : 1
        //  },
        //  "securityCode" : {
        //    "code" : "056901",
        //    "tooManyCodesSent" : false,
        //    "tooManyCodesValidated" : false,
        //    "securityCodeLocked" : false,
        //    "securityCodeCooldown" : false,
        //    "valid" : true
        //  },
        //  "mode" : "sms",
        //  "type" : "verification",
        //  "authenticationType" : "hsa2",
        //  "recoveryUrl" : "https://iforgot.apple.com/phone/add?prs_account_nm=judy7024895%40hotmail.com&autoSubmitAccount=true&appId=142",
        //  "cantUsePhoneNumberUrl" : "https://iforgot.apple.com/iforgot/phone/add?context=cantuse&prs_account_nm=judy7024895%40hotmail.com&autoSubmitAccount=true&appId=142",
        //  "recoveryWebUrl" : "https://iforgot.apple.com/password/verify/appleid?prs_account_nm=judy7024895%40hotmail.com&autoSubmitAccount=true&appId=142",
        //  "repairPhoneNumberUrl" : "https://gsa.apple.com/appleid/account/manage/repair/verify/phone",
        //  "repairPhoneNumberWebUrl" : "https://appleid.apple.com/widget/account/repair?#!repair",
        //  "aboutTwoFactorAuthenticationUrl" : "https://support.apple.com/kb/HT204921",
        //  "autoVerified" : false,
        //  "showAutoVerificationUI" : false,
        //  "supportsCustodianRecovery" : false,
        //  "hideSendSMSCodeOption" : false,
        //  "supervisedChangePasswordFlow" : false,
        //  "trustedPhoneNumber" : {
        //    "numberWithDialCode" : "+86 ••• •••• ••70",
        //    "pushMode" : "sms",
        //    "obfuscatedNumber" : "••• •••• ••70",
        //    "lastTwoDigits" : "70",
        //    "id" : 1
        //  },
        //  "hsa2Account" : true,
        //  "restrictedAccount" : false,
        //  "supportsRecovery" : true,
        //  "managedAccount" : false
        //}
        //0
    }

    public function validateCode ()
    {
        //POST https://idmsa.apple.com/appleauth/auth/verify/phone/securitycode HTTP/1.1
        //Connection: Keep-Alive
        //Content-Type: application/json; Charset=UTF-8
        //Accept: application/json
        //Accept-Language: zh-CN,zh;q=0.9
        //Cookie: idclient=web; dslang=CN-ZH; site=CHN; aidsp=5AE91CA520F126BB639F6EF86BFBC13F632BB3E9408918586B2E52A4D018D883EB800E2716D1EACE3342A59372E9169DA97340575C8767C9B40BF80CCC470240074D1E811ABEAE563F2F2C3E5A5450B28D8C905E12B1E52A03F47AA53F0693A67359EB05904504E24E87AA5C23DBEC87215360C47489024D; aasp=8636822502970A4E9B08F456946CEB584007323F8EE4BAB529A908DAB6B8C1122AD852AC96120D0B29B2EAE927F78107A130E580DD6570A47A33D1F8DB6E8F1EB8C029630E6EC23A80CB185E7068BC5EC3EF8A6483DB6DE93774ACB2408FB5B807AD809F6A96418EC44B5C6A60D2910C6C08F012E71A782F; acn01=cB+lPoyojgRej/5U7tXKgdI1UnzGqPU09Ifu5bqDIt8AHI/pJhVakA==; crsc=UusV4eNwm8U5yVAQJPxX4i5UNkcZT+vQsFQ+CtwAQ9vmtWOkMkBUB7M6uuHXpPpqrYL5P2CUKUdJyEIysQAgnWibFC1A
        //Host: idmsa.apple.com
        //Referer: https://idmsa.apple.com/
        //User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36
        //scnt: AAAA-jg2MzY4MjI1MDI5NzBBNEU5QjA4RjQ1Njk0NkNFQjU4NDAwNzMyM0Y4RUU0QkFCNTI5QTkwOERBQjZCOEMxMTIyQUQ4NTJBQzk2MTIwRDBCMjlCMkVBRTkyN0Y3ODEwN0ExMzBFNTgwREQ2NTcwQTQ3QTMzRDFGOERCNkU4RjFFQjhDMDI5NjMwRTZFQzIzQTgwQ0IxODVFNzA2OEJDNUVDM0VGOEE2NDgzREI2REU5Mzc3NEFDQjI0MDhGQjVCODA3QUQ4MDlGNkE5NjQxOEVDNDRCNUM2QTYwRDI5MTBDNkMwOEYwMTJFNzFBNzgyRnwxAAABkIlVsCmLm4nyeeI7_QA8dTqv4WXZcabPqSyNwtih3s6YKmKGatRkLAj6F-DfAByP6Ru-8ycwaF0jAyzn5AMXMeBB2gZl52IsP7TaSD_xuscq6I7HTA
        //X-Apple-Widget-Key: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Apple-OAuth-Redirect-URI: https://appleid.apple.com
        //X-Apple-OAuth-Client-Id: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Apple-OAuth-Client-Type: firstPartyAuth
        //x-requested-with: XMLHttpRequest
        //X-Apple-ID-Session-Id: 8636822502970A4E9B08F456946CEB584007323F8EE4BAB529A908DAB6B8C1122AD852AC96120D0B29B2EAE927F78107A130E580DD6570A47A33D1F8DB6E8F1EB8C029630E6EC23A80CB185E7068BC5EC3EF8A6483DB6DE93774ACB2408FB5B807AD809F6A96418EC44B5C6A60D2910C6C08F012E71A782F
        //X-Apple-Auth-Attributes: TTt7fcGisp/RfkCBmAZ7QETn4phtbt0nrjW38is5PR8zQkuxpJyAWRVKLKeiPOWWHPUpeLLnR9LUM1Pgr2wPGcq1kj3SFCGtGH4GPOAhcS8PPs4ZG421rSKTj/LCYZd4BU8NXtBWjDBGEOFQmuEuMqSNaMxuERK1Gbn/oDBCWwEkczqRD/KnBlygf9Z/71eVHQoeN+I2VKAjAQAcj+kmJE2D
        //X-Apple-OAuth-Response-Type: code
        //X-Apple-OAuth-Response-Mode: web_message
        //X-Apple-Domain-Id: 1
        //Origin: https://idmsa.apple.com
        //Sec-Fetch-Site: same-origin
        //Sec-Fetch-Mode: cors
        //Sec-Fetch-Dest: empty
        //Content-Length: 70
        //
        //{"phoneNumber":{"id":1},"securityCode":{"code":"114562"},"mode":"sms"}


    }

    /**
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function accountManageToken(): Response
    {
        //GET https://appleid.apple.com/account/manage/gs/ws/token HTTP/1.1
        //Connection: Keep-Alive
        //Content-Type: application/json
        //Accept: application/json, text/plain, */*
        //Accept-Language: zh-CN,zh;q=0.9
        //Cookie: idclient=web; dslang=CN-ZH; site=CHN; aidsp=0562F50FE3CE81366007E772369D847191759222C8851F85F842B48BB6323D128458D33CFF50F28EAF21502DDE47109F5D186973240EC682743F7EC07B58FF37F23F46271BF3DD82DDDE8FE2593B3F41524AB77ECEDC62E50466EB1732BDA349CCF8835FF15126030CEEE03E82F36A42BEA84366555DF018; aasp=F80190F4FD80B689D2B92B383AC4B8C1EF55795E9C47C39C777D7E1AEC4452F12A219F6F540379F6A361BE1685C494DE38C6973A08A755B3EDA3F1326F3718C2B1C78BBFE5A455EA9D41C556D182961CC385907710EF70A3E8CEC61CA509CA668750EF60BD570D42021E94AA1A41BE2F5F3149E1C1B3AFAA; acn01=ZcT6K8g6hiycDKk/DzoSUlmT+dj134Hmy4OkJeCr9wA1QC8/Tjvk; myacinfo=DAWTKNV323952cf8084a204fb20ab2508441a07d02d356c9a9e48916520085c3cbc21c2532dfc92ea37f2d8ede62a39be232133c5b17365830bdd1d11fc83bdd5a077cd024d0195452d5b7ba7f82243d75b919028cbc500bc0bccdb8fe9a8ee5b548574bb8919822ecb359d2a8fed7bdd02f967b8edd17f48fbe2bf5326132af77fdb6a476db697aac8db8f32475f527d4340d326dca8bf65d54160ae2772374477146e989ad7d142166206ef25ae7da769621a574370f423021a807beebd2bbeea26471bf426bd3b64165a56fb77df9aa7bfb2082a541a43463d7d03097858f3a9cbba9705b7a10dadd57522ba6b0ff08ad4eba9ba5b58dc78b096c4f42fd8221b970a3ec848008da4e07f20a83483c7170657e121e9c4d24bca18cb6c54eb824c1e81d87b97d4f036aed61325476228026681244c319b05660d4e356f2a9d393763882b27a8524f28b0919ddd7416844f1bb795a74bac9c10ec1f7204ad2d92e0b74c07c5ded651dcc79b94b8b85db8c5497503c70245ea10a1e782572fcef7778ba8d4e7e293edfae2102b192d4fc099aa5510757200249cabfc4e0ea486972abfd8ee335a0190503148681e11e0037000c12b5194ca4b371fa4507ce9ca113a67d36ebf68ec81d01cd0035ec3963746ee9b90a31ab3716bc89d3df848d91be34cd9815260a4e1a8b4ee0f3df462e99f02e66657153b9ea5a8fe8acbb6fa7adb6e7a02e12cbb570a9b3975358e6fa343012d644b6d9452ea025b2e5d416171c5caf395e895b172c3c963f0542a1d21c952d48fc7d165250d84147083997c6b9de74a53b681ad2d963132e2699782e4a9b439193a61aeb695b45d1e350b2b6726efe8c488e10cfec4fd5b8c011ef5d5e0fc35cb4c54632741b5ada51c1fefa108e88c4b3fb5e28b0077ac075653269887fd337f4fc089c4a787c77aae122ebbcf849807df2e92244fc5333d8c74f56b8ca9b56403a7004bd4480ad17dfa2268a811b2fda3f585a47V3
        //Host: appleid.apple.com
        //Referer: https://appleid.apple.com/
        //User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36
        //scnt: AAAA-kY4MDE5MEY0RkQ4MEI2ODlEMkI5MkIzODNBQzRCOEMxRUY1NTc5NUU5QzQ3QzM5Qzc3N0Q3RTFBRUM0NDUyRjEyQTIxOUY2RjU0MDM3OUY2QTM2MUJFMTY4NUM0OTRERTM4QzY5NzNBMDhBNzU1QjNFREEzRjEzMjZGMzcxOEMyQjFDNzhCQkZFNUE0NTVFQTlENDFDNTU2RDE4Mjk2MUNDMzg1OTA3NzEwRUY3MEEzRThDRUM2MUNBNTA5Q0E2Njg3NTBFRjYwQkQ1NzBENDIwMjFFOTRBQTFBNDFCRTJGNUYzMTQ5RTFDMUIzQUZBQXwxAAABkGjgCfKJK6nuKKmEwMjCJLiF4jgHS4k5iQkYb00IpWDb2V4JxZga1PHIQBCrADVALzRCMekvs-St0xxzBHzLCU_FvSnqCX5sYKQbGcYeTiE9ZOU-Ig
        //X-Apple-I-Request-Context: ca
        //X-Apple-I-TimeZone: Asia/Shanghai
        //Sec-Fetch-Site: same-origin
        //Sec-Fetch-Mode: cors
        //Sec-Fetch-Dest: empty

        return $this->request('get','/account/manage/gs/ws/token');

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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function password(string $password):Response
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

        $response = $this->request('POST', '/authenticate/password', [
            RequestOptions::JSON => [
                'password' => $password,
            ],
            RequestOptions::HTTP_ERRORS => false,
        ]);

        if($response->getStatus() !== 204) {
            throw new UnauthorizedException($response->getFirstErrorMessage(),$response->getStatus());
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

    //从新发送验证吗
    //https://appleid.apple.com/account/manage/security/verify/phone HTTP/1.1
    public function accountManageSecurityVerifyPhone(string $phoneNumber, string $countryCode, string $countryDialCode,bool $nonFTEU = true)
    {
        //POST https://appleid.apple.com/account/manage/security/verify/phone HTTP/1.1
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

        return $this->request('POST', '/account/manage/security/verify/phone', [

            RequestOptions::JSON => [
                'phoneNumberVerification' => [
                    'phoneNumber' => [
                        'countryCode' => $countryCode,
                        'number' => $phoneNumber,
                        'countryDialCode' => $countryDialCode,
                        'nonFTEU' => $nonFTEU,
                    ],
                    'mode' => 'sms',
                    ],
                ],
        ]);
        //{
        //  "phoneNumberVerification" : {
        //    "phoneNumber" : {
        //      "nonFTEU" : true,
        //      "numberWithDialCode" : "+1 (945) 313-0454",
        //      "deviceType" : false,
        //      "iMessageType" : false,
        //      "pacType" : false,
        //      "complianceType" : false,
        //      "appleComplianceType" : false,
        //      "numberWithDialCodeAndExtension" : "+1 (945) 313-0454",
        //      "rawNumber" : "9453130454",
        //      "fullNumberWithCountryPrefix" : "+1 (945) 313-0454",
        //      "sameAsAppleID" : false,
        //      "countryCode" : "US",
        //      "verified" : false,
        //      "countryDialCode" : "1",
        //      "number" : "(945) 313-0454",
        //      "pushMode" : "sms",
        //      "vetted" : false,
        //      "createDate" : "06/30/2024 11:08:49 AM",
        //      "updateDate" : "06/30/2024 11:08:49 AM",
        //      "rawNumberWithDialCode" : "+19453130454",
        //      "pending" : true,
        //      "lastTwoDigits" : "54",
        //      "loginHandle" : false,
        //      "countryCodeAsString" : "US",
        //      "obfuscatedNumber" : "(•••) •••-••54",
        //      "name" : "+1 (945) 313-0454",
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
        //    "countryCode" : "US",
        //    "countryDialCode" : "1",
        //    "number" : "(945) 313-0454",
        //    "keepUsing" : false,
        //    "changePhoneNumber" : false,
        //    "simSwapPhoneNumber" : false,
        //    "addDifferent" : false
        //  },
        //  "countries" : [ {
        //    "code" : "ALB",
        //    "code2" : "AL",
        //    "name" : "阿尔巴尼亚",
        //    "dialCode" : "355",
        //    "embargoed" : false,
        //    "underAgeLimit" : 13,
        //    "minorAgeLimit" : 18,
        //    "supportHSA2" : true,
        //    "supportPaidAccount" : false,
        //    "japan" : false,
        //    "korea" : false,
        //    "usa" : false,
        //    "canada" : false,
        //    "uk" : false,
        //    "dialCodeDisplay" : "+355 (阿尔巴尼亚)"
        //  }
    }

    public function getPhoneCode(string $url):?Response
    {
        $client = new Client([

        ]);

        $response = $client->get($url,[
            RequestOptions::HTTP_ERRORS => false,
        ]);

        // "code":10000,
        //        "message":"success",
        //        "data":{
        //            "fromNumber":14687944569,
        //            "toNumber":15856931105,
        //            "time":1707792795,
        //            "message":"Telegram code: 78890You can also tap on this link to log in:https://t.me/login/78890"
        //        }

        if (10000 !== $response->getStatusCode()) {
            return null;
        }

        return $this->parseJsonResponse($response);
    }

    /**
     * 重新发送验证码
     * @return Response
     * @throws UnauthorizedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function SendSecurityCode ()
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
        $response =  $this->request('PUT', '/appleauth/auth/verify/trusteddevice/securitycode', [
            RequestOptions::HTTP_ERRORS => false,
        ]);


        if (!in_array($response->getStatus(),[202, 412])){
            throw new UnauthorizedException($response->getFirstErrorMessage(),$response->getStatus());
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
     * 验证安全代码
     * @param string $code
     * @return Response
     * @throws UnauthorizedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function authverifytrusteddevicesecuritycode(string $code): Response
    {
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

        return $this->request('post','/appleauth/auth/verify/trusteddevice/securitycode', [
            RequestOptions::JSON=>[
                'securityCode'=>[
                    'code'=>$code,
                ]
            ],
        ]);
    }
    /**
     * 发送手机验证码
     * @param int $id
     * @return Response
     * @throws UnauthorizedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendAuthVerifyPhoneSecurityCode(int $id): Response
    {
        //423
        //{"phoneNumber":{"id":1},"mode":"sms"}
        //https://idmsa.apple.com/appleauth/auth/verify/phone HTTP/1.1

        return $this->request('put','/appleauth/auth/verify/phone', [
            RequestOptions::JSON=>[
                'phoneNumber'=>[
                    'id'=>$id,
                ],
                'mode'=>'sms',
            ],
        ]);
    }

    //https://appleid.apple.com/account/manage/icloud/sharing/phone
    //{
    //    "phoneNumber": {
    //        "countryCode": "US",
    //        "countryDialCode": "1",
    //        "number": "(908) 471-2268"
    //    },
    //    "securityCode": {
    //        "code": "157640"
    //    },
    //    "mode": "sms"
    //}
}
