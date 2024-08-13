<?php

namespace App\Apple\Service\Client;

use App\Apple\Service\Exception\AccountLockoutException;
use App\Apple\Service\Exception\UnauthorizedException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

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
        //\"supervisedChangePasswordFlow\" : false,
        //  \"hsa2EnrollmentMandatory\" : false,
        //  \"securityQuestions\" : {
        //    \"questions\" : [ {
        //      \"id\" : 130,
        //      \"question\" : \"你少年时代最好的朋友叫什么名字？\",
        //      \"number\" : 1,
        //      \"userDefined\" : false
        //    }, {
        //      \"id\" : 136,
        //      \"question\" : \"你的理想工作是什么？\",
        //      \"number\" : 2,
        //      \"userDefined\" : false
        //    } ]
        //  },
        //  \"crResetEnabled\" : false,
        //  \"resetSecurityQuestionsSupportLink\" : \"http://support.apple.com/kb/HT6170\"
        //}"}
    }

    /**
     * 双重认证首页
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function auth(): ResponseInterface
    {
        return $this->getClient()->request('GET', '/appleauth/auth', [
            RequestOptions::HEADERS => [
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
     * 验证手机验证码
     * @param int $id
     * @param string $code
     * @return Response
     * @throws GuzzleException
     */
    public function validatePhoneSecurityCode(string $code, int $id = 1): Response
    {
        return $this->request('post', '/appleauth/auth/verify/phone/securitycode', [
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
            RequestOptions::HTTP_ERRORS => false,
        ]);

        //HTTP/1.1 412
        //Server: Apple
        //Date: Tue, 06 Aug 2024 16:19:52 GMT
        //Content-Type: application/json;charset=UTF-8
        //Transfer-Encoding: chunked
        //Connection: keep-alive
        //X-Apple-I-Request-ID: b5f43e38-540f-11ef-9481-234c6b419a92
        //X-FRAME-OPTIONS: DENY
        //X-Content-Type-Options: nosniff
        //X-XSS-Protection: 1; mode=block
        //Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
        //Content-Security-Policy: default-src 'self' ; child-src blob: ; connect-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://webcourier.sandbox.push.apple.com https://xp-qa.apple.com ; font-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ; frame-src 'self' https://appleid.apple.com https://gsa.apple.com ; img-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://*.mzstatic.com data: https://*.apple.com ; media-src data: ; object-src 'none' ; script-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ; style-src 'unsafe-inline' 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ;
        //Referrer-Policy: origin
        //X-BuildVersion: R14_1
        //scnt: AAAA-kJEQ0RBN0MwRkE2MUUxRjc5REU0RDY3MjY2MzA0ODNDNzYxNkNGNEIwNzE2MDA1Qjc3M0QwRkU5QUQwMkNCQzNBMjJEQTEyM0Y5REJEQzVDNjVFMjIzRjJGQUE3QzVBRkFDRTFFODNFRkZGOUQ2M0IwMTVDNjg0RjQxODBCMDA3NkMwRjY2NEMwN0MwM0QxQjg3MTlCOTJBNzBENEM1NjAzMTdFNDlCRkFEODUwNTM0NEVEM0Q1MjkyNUY2QTNGNkYyOTdERUVDREY2MjkyN0RDNTZEOURCN0JDQzQxOUI3RDRDRjQ5N0ZBQzM0MDE2NXw2AAABkSiLIiXa6AZu9MN_-ST_cRf9YaWvhBMDAtzgMdEvQRqBu3AW86ZdNFO6VoDSACYAAx2vKgBiddLBEUNNcQZ1e-w5r7ueThMuMGpvbdPQUKa4lDKpKQ
        //Set-Cookie: dslang=US-EN; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: site=USA; Domain=apple.com; Path=/; Secure; HttpOnly
        //Pragma: no-cache
        //Expires: Thu, 01 Jan 1970 00:00:00 GMT
        //Cache-Control: no-cache
        //Cache-Control: no-store
        //X-Apple-Repair-Session-Token: uUJYYr8at8z3PIhJTfxEqtiIxC4VlU9K346Q2/BqUZzNo4bG+TTTt/MyPDFNLT9b80X9g0x2FrzToLIT6pFeUgzn2nvkZBJY1Gb8CI4qDgH9HbyuAujyXkZx1gWrWW1gDWT5TTgwkn5fTKlsyAa+HdGsf3PCuCOXwD9DabmibwlTvnErccjlUpH0iwJyT89U855ewNfcitr6bi8peiUvkWQSrZq/IteXt0VnhY1Of7v6lFPIDB1S+gEA9DoUiIwDdvmN9/WPu0LlpwOeOGiO/juYxUZRllb0lbCcz4U/HZyKRdhcDNGDzCHgcxmwf9mVICLA/pj5UAyie15AyqGAKL67JaYv8keLL+CHcyhNLFRGgIbtGRt4NRJGbhl9Q+eCq40Eq2rYg5pyxi33MiWa54yc2Zys8DP4Cs/uU0J+DunLp9ab+dQ4Z9WpmotNv4ykM0VVqT+U9JnA8ZdDTtmSq4CycpYgUzXDXRRiAmw+LCxciyvdPqomwMsQPDDpOZ5ohOtDRbDKd9NQykAYQXxy7WZ4llob+3ipJEUUPm6TrABxCtc3Whf6sBf1rimxzFuo2XUlgzOGwcqtuTMqJcc/CwzMN5dp7bpzAN1FXVMBlWXXimDsAgNAFSzwo63RfEi9hg8iEyiGAVESwvJEK5Pad0mvJoz0m6BSzsULBNOfCPaYEVhgRxJvxIGs+TKVdl8B1BZOplAtaXTR9vRht3hPddaS2uQmAszzThP3+lygSyh8E4Uz0Zj8Qh09BOgrDXDMNOD1U2jqTpgXRe4qj0SahYDqjIueS/EZNDQeJrf/oPInK6biU0prgBM8cVILATP4nvD6t8uB+AOIwibdWmTKzhC/fbbf38gzUj8mj1sah615kBxtQJ1B+mKCd00EC7aXbgImwmw67v2f7aCAOLhE7p8U9DFV+jnLr6d6Lp7LqxS2t7KRe9HTAF/IqS3yd54f2MrkYNZUqOUxTe/WVXbjaIOyLVUjxTV2fcsAr391YD4JVwaEmnbqfH1wtHputdzF6Ws52SI+G5arKVy/7Ckrmqdpup9cgTgzrPmU3FcA88EESGJjH8kR7hjAAaBVE0lYWb7YONnk5NfulFtmY/nH/E7Y79+Ha5MUfQZoNxeqOh8YBlS1/Ld7SMqgqhSrBb6Bp7S/yCu53uuEG87K+TxQeTHeHeHYUNweVTnPrsXFYWNPiqENV0RKktHI2Khq5wOMNw2bACYAAySZvr4=
        //X-Apple-OAuth-Context: Oo9rvSiVSU/oLnmTrKXFj/0XanM/Dj7dz8SZfPSYAQG0i8tnk/FX5yJkE8vNcW0PJEfeVDJYA0Gmi9kzaE6WVA9aLJn199DC1DTwMAQXfFkPcbPxzxyPLAsBFqMvOZqkVQ9P8z0/xxzoMklcBu/C6eTnuuMPeetR4uQuSECg7rkZD0U0BEChk5kQunMewPoFXeTAIUwztEuiehSmk4llyT38ACYAAySa7qA=
        //Location: https://appleid.apple.com/widget/account/repair?widgetKey=af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3&rv=1&language=en_US_USA#!repair
        //X-Apple-ID-Session-Id: BDCDA7C0FA61E1F79DE4D6726630483C7616CF4B0716005B773D0FE9AD02CBC3A22DA123F9DBDC5C65E223F2FAA7C5AFACE1E83EFFF9D63B015C684F4180B0076C0F664C07C03D1B8719B92A70D4C560317E49BFAD8505344ED3D52925F6A3F6F297DEECDF62927DC56D9DB7BCC419B7D4CF497FAC340165
        //X-Apple-Auth-Attributes: zQItPR1HwMKynpC+n+tAyNBbIeKL1DKQqOJ9kvynJhTJeRKge7xOti3YoDnvN7yRVQxEdqQWYkLpNduyahgBbN5x4kQzwlaWWEIkstLmpyx/17DANZIeB6k7FYmKPgSTNi8ho3sQyHcvCjQRJcFM1e/KXRQVl/ooyfXUz2INa0i4hB8W6z6MxF+1orwAlRr79tBZ6gRtxK9cENKxYkJYQHnXXALA7rD3EtoUJQfqQ2BM0jIGVyM03OTVl4eJ/R/OZt3+ACYAAySinK4=
        //X-Apple-ID-Account-Country: CHN
        //Cache-Control: no-store
        //vary: accept-encoding
        //Content-Encoding: gzip
        //Content-Language: en-US-x-lvariant-USA

        //{
        //  "trustedDeviceCount" : 1,
        //  "otherTrustedDeviceClass" : "iPhone",
        //  "securityCode" : {
        //    "code" : "752635",
        //    "tooManyCodesSent" : false,
        //    "tooManyCodesValidated" : false,
        //    "securityCodeLocked" : false,
        //    "securityCodeCooldown" : false,
        //    "valid" : true
        //  },
        //  "phoneNumberVerification" : {
        //    "trustedPhoneNumbers" : [ {
        //      "numberWithDialCode" : "+86 ••• •••• ••80",
        //      "pushMode" : "sms",
        //      "obfuscatedNumber" : "••• •••• ••80",
        //      "lastTwoDigits" : "80",
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
        //    "recoveryUrl" : "https://iforgot.apple.com/phone/add?prs_account_nm=8615875394380&autoSubmitAccount=true&appId=142",
        //    "cantUsePhoneNumberUrl" : "https://iforgot.apple.com/iforgot/phone/add?context=cantuse&prs_account_nm=8615875394380&autoSubmitAccount=true&appId=142",
        //    "recoveryWebUrl" : "https://iforgot.apple.com/password/verify/appleid?prs_account_nm=8615875394380&autoSubmitAccount=true&appId=142",
        //    "repairPhoneNumberUrl" : "https://gsa.apple.com/appleid/account/manage/repair/verify/phone",
        //    "repairPhoneNumberWebUrl" : "https://appleid.apple.com/widget/account/repair?#!repair",
        //    "aboutTwoFactorAuthenticationUrl" : "https://support.apple.com/kb/HT204921",
        //    "autoVerified" : false,
        //    "showAutoVerificationUI" : false,
        //    "supportsCustodianRecovery" : false,
        //    "hideSendSMSCodeOption" : false,
        //    "supervisedChangePasswordFlow" : false,
        //    "supportsRecovery" : true,
        //    "trustedPhoneNumber" : {
        //      "numberWithDialCode" : "+86 ••• •••• ••80",
        //      "pushMode" : "sms",
        //      "obfuscatedNumber" : "••• •••• ••80",
        //      "lastTwoDigits" : "80",
        //      "id" : 1
        //    },
        //    "hsa2Account" : true,
        //    "restrictedAccount" : false,
        //    "managedAccount" : false
        //  },
        //  "aboutTwoFactorAuthenticationUrl" : "https://support.apple.com/kb/HT204921"
        //}

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

    /**
     * @return Response
     * @throws GuzzleException
     */
    public function appleAuthRepairComplete(): Response
    {
        return $this->request('POST', '/appleauth/auth/repair/complete',[
            RequestOptions::HEADERS => [
                'X-Apple-ID-Session-Id'   => $this->cookieJar->getCookieByName('aasp')->getValue(),
                'X-Apple-Auth-Attributes' => $this->user->getHeader('X-Apple-Auth-Attributes') ?? '',
                'X-Apple-Repair-Session-Token' => $this->user->getHeader('X-Apple-Repair-Session-Token') ?? '',
            ],
            RequestOptions::HTTP_ERRORS => false,
        ]);
    }
}
