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
        //POST /appleauth/auth/repair/complete HTTP/1.1
        //Accept: application/json;charset=utf-8
        //Accept-Encoding: gzip, deflate, br, zstd
        //Accept-Language: en,zh-CN;q=0.9,zh;q=0.8
        //Connection: keep-alive
        //Content-Length: 0
        //Content-Type: application/json
        //Cookie: dssid2=359a4ccc-4628-4877-aab3-3b780a862304; dssf=1; as_sfa=Mnx1c3x1c3x8ZW5fVVN8Y29uc3VtZXJ8aW50ZXJuZXR8MHwwfDE; pldfltcid=c30f6120e0484dc6b71e6e784a61e337012; pltvcid=undefined; pxro=1; POD=us~en; nn-user-journey=%7B%22value%22%3A%7B%22os%22%3A%2215.0.0%3B%22%2C%22device%22%3A%22WINDOWS%3B%20X86%3B%2064%3B%22%2C%22journey%22%3A%7B%22lastPath%22%3A%22https%3A%2F%2Fsecure5.store.apple.com%2Fshop%2Faccount%2Fhome%22%2C%22stitchPath%22%3A%5B%22no-referrer%22%2C%22%2Fen-us%2F102637%22%2C%22%2F%22%2C%22https%3A%2F%2Fwww.apple.com%2Fus%2Fshop%2Fgoto%2Fbag%22%2C%22https%3A%2F%2Fsecure5.store.apple.com%2Fshop%2Faccount%2Fhome%22%5D%7D%7D%2C%22expiry%22%3A1722790639745%7D; geo=CN; s_cc=true; as_pcts=rCCnITwRN4TNkb6mbNEuEw32q+HXBLjBmr6xKFP_:RTAi4mSxW_qKpZmPmC-YnDyc+ek:ZsvSql2FL+S3ToQhVYTg56CsyGYe04Owbd6oZuIaS5aMoGi6ZxpOd:SWTrCkQaNEaQd_--SgZaifenkKnRwq6J2oCiQwh:5xm645iP6ENZOU; as_dc=ucp4; dslang=US-EN; site=USA; as_rumid=6f4cf224-9877-4547-8ed2-43996e2595b8; itspod=31; s_fid=7D87CF4BB6BF5B1A-3103566EF86D8AF7; s_sq=%5B%5BB%5D%5D; pt-dm=v1~x~ayvotxez~m~3~n~AOS%3A%20checkout%20%3A%20sign%20in~r~aos%3Aaccount; aasp=BDCDA7C0FA61E1F79DE4D6726630483C7616CF4B0716005B773D0FE9AD02CBC3A22DA123F9DBDC5C65E223F2FAA7C5AFACE1E83EFFF9D63B015C684F4180B0076C0F664C07C03D1B8719B92A70D4C560317E49BFAD8505344ED3D52925F6A3F6F297DEECDF62927DC56D9DB7BCC419B7D4CF497FAC340165; aa=98B70E2ABC1D55A1CB8088909A66DD8E
        //Host: idmsa.apple.com
        //Origin: https://idmsa.apple.com
        //Referer: https://idmsa.apple.com/
        //Sec-Fetch-Dest: empty
        //Sec-Fetch-Mode: cors
        //Sec-Fetch-Site: same-origin
        //User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36
        //X-Apple-Auth-Attributes: IvRkliN7i5z0iwWhIegW3c14dJqItO8mvVFkSumtIl1wjEp6Y80JXivCm5Izb7tu09wIKshXonoSYCJiaV8TYQt6oRNUEGHcFeogtIWLX+8/xHGxjlVkNi8cLc/VuFFfW++IXBGLWXeUYQOz++1bipoA1LAnBAeBRGK6NEmEtoqymTR8mWSNBLdv9sqPXAka1u9ihQeFbx1OQGqU4PfnlHUbIoqNrNrhUO1qudcIhasya8Tw7OLELBq7dBPh2KqkDTtVACZRorxpIG8=
        //X-Apple-Domain-Id: 1
        //X-Apple-Frame-Id: auth-fjh8985y-vlwk-vxl2-2zhm-sr1lede2
        //X-Apple-I-FD-Client-Info: {"U":"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36","L":"en","Z":"GMT+08:00","V":"1.1","F":".la44j1e3NlY5BNlY5BSmHACVZXnNA9dMN0I0K13hyhpAI6.D_xGMuJjkW5BOfs.xLB.Tf1cK0DubHyhUeBz1cNlY5BNp55BNlan0Os5Apw.1lJ"}
        //X-Apple-ID-Session-Id: BDCDA7C0FA61E1F79DE4D6726630483C7616CF4B0716005B773D0FE9AD02CBC3A22DA123F9DBDC5C65E223F2FAA7C5AFACE1E83EFFF9D63B015C684F4180B0076C0F664C07C03D1B8719B92A70D4C560317E49BFAD8505344ED3D52925F6A3F6F297DEECDF62927DC56D9DB7BCC419B7D4CF497FAC340165
        //X-Apple-OAuth-Client-Id: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Apple-OAuth-Client-Type: firstPartyAuth
        //X-Apple-OAuth-Redirect-URI: https://appleid.apple.com
        //X-Apple-OAuth-Response-Mode: web_message
        //X-Apple-OAuth-Response-Type: code
        //X-Apple-OAuth-State: auth-fjh8985y-vlwk-vxl2-2zhm-sr1lede2
        //X-Apple-Repair-Session-Token: UDzWOFsLGB7SEZLEU7Pgh9vdp8w1WGXWYxo+8zDpXZwdxUG2ddEKVSbtXkuLrULP0LzFlxk6CVfKBgth/aFMIlune4O4OZYMRPMSjql/L+5nAmnz6G0EdqRTaNl3oS5l8GRnHrxTa+iU4Jl1Ar9d1/grHm/8nt82x7NbD3PPwK+jCmL2mPN3yt6f4/zqvu9VjPcDw7uOFtHMAA+xpWRLIOFVGJsvNWP47I1yWY1SUcJk7jaKC2AK1CL7UG6JrofTOLnK701hcFt5Rmnoi3fRoUBWY77StmTvL3NfK6yWQ2CDfVyfJZTVVs1R5AYakGvrT5omkDTFUk9DjQ0U/FAnJZgQgxonOTgEVgydluXGkciNf0O5VXmkhOXtcPDPjyl7sl6P2Tt2CpPm5NTJmJsSUQTUIG3chY5gYRAptMEdwyNlh0TLRMNDdQtdfcVmFkIXr/++8oKTIMyEX8kR1jFRoJKoY6fTmICxHDARqnW1MkciM9TSG4yzMu6uix6/ScUJ+kJxqfUWir9ruK3aYcO/VZV4e9+QRXKG2JF+HFh6QQmqkrv1dhXPwBmW2KKqLBzpOXqq8onloLteo2yikNfnefnRTgh+0O+ZbqYdZfowxOD0SX/Uku/yve2+U7WhtquLQROAIygGsn69yJMgR+jeZyjHuA+/x/Xv0ubzbwLtMm7i4KMr4kJcx5DSEUvQ+pGalgUA9MHI3LcDaeum0MPnp7AwJQFO89Ta3T7o1CiL/Gd5z0oQgEP7RuzQPa3cuEv+mkRhIFpCZUlLPEVz46t+KM2I9xWHtP1oCO0Zik8FTZUHKQRJzpLJ2qO6u3ja6F+iQ5hMORv1s1U2iBF0zRX+mtcnBTXY0LEjC9z4kz7JlWVYBA0LjL5wqy5skvh058lmVcdZMv+hsrbGHwUPb4ZSiHP1vL1HdrAVyZx3CEjDZxSuYOCJDJpuwi7KgvjNWimzW33p8rGbDIM/YV3P4G48/UM+f+wXbM7Ktb8ACADCuuS84VHjxRYGKRIVw/13d8HEwzsvwcbyBJphQyQVAlehzqKf4rHwh9EwziH8WQhmYf5W/O6MYZJ6/FK5jvb9vRkoKeNtUZkd8FGsg/RXqQOM0Bcl5HL3eyxBE5Dq9rekTyW2wvWGhd/7hT9Uag4Y0j9lQUiQ6K04Yvd6JghvIq7+3ZKvxns1Ii1rw3qErJhb/Jto1mhDqjkdMDraRUQyRpjQvxMAJOSm+J59fQ==
        //X-Apple-Widget-Key: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Requested-With: XMLHttpRequest
        //scnt: AAAA-kJEQ0RBN0MwRkE2MUUxRjc5REU0RDY3MjY2MzA0ODNDNzYxNkNGNEIwNzE2MDA1Qjc3M0QwRkU5QUQwMkNCQzNBMjJEQTEyM0Y5REJEQzVDNjVFMjIzRjJGQUE3QzVBRkFDRTFFODNFRkZGOUQ2M0IwMTVDNjg0RjQxODBCMDA3NkMwRjY2NEMwN0MwM0QxQjg3MTlCOTJBNzBENEM1NjAzMTdFNDlCRkFEODUwNTM0NEVEM0Q1MjkyNUY2QTNGNkYyOTdERUVDREY2MjkyN0RDNTZEOURCN0JDQzQxOUI3RDRDRjQ5N0ZBQzM0MDE2NXw2AAABkSiLIiXa6AZu9MN_-ST_cRf9YaWvhBMDAtzgMdEvQRqBu3AW86ZdNFO6VoDSACYAAx2vKgBiddLBEUNNcQZ1e-w5r7ueThMuMGpvbdPQUKa4lDKpKQ
        //sec-ch-ua: "Not)A;Brand";v="99", "Google Chrome";v="127", "Chromium";v="127"
        //sec-ch-ua-mobile: ?0
        //sec-ch-ua-platform: "Windows"
        return $this->request('POST', '/appleauth/auth/repair/complete',[
            RequestOptions::HEADERS => [
                'X-Apple-ID-Session-Id'   => $this->cookieJar->getCookieByName('aasp')->getValue(),
                'X-Apple-Auth-Attributes' => $this->user->getHeader('X-Apple-Auth-Attributes') ?? '',
                'X-Apple-Repair-Session-Token' => $this->user->getHeader('X-Apple-Session-Token') ?? '',
            ],
            RequestOptions::HTTP_ERRORS => false,
        ]);

        //HTTP/1.1 204
        //Server: Apple
        //Date: Tue, 06 Aug 2024 16:25:43 GMT
        //Connection: keep-alive
        //X-Apple-I-Request-ID: 87240773-5410-11ef-bc48-bdae39b00f0f
        //X-FRAME-OPTIONS: DENY
        //X-Content-Type-Options: nosniff
        //X-XSS-Protection: 1; mode=block
        //Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
        //Content-Security-Policy: default-src 'self' ; child-src blob: ; connect-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://webcourier.sandbox.push.apple.com https://xp-qa.apple.com ; font-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ; frame-src 'self' https://appleid.apple.com https://gsa.apple.com ; img-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://*.mzstatic.com data: https://*.apple.com ; media-src data: ; object-src 'none' ; script-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ; style-src 'unsafe-inline' 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://signin.apple.com ;
        //Referrer-Policy: origin
        //X-BuildVersion: R14_1
        //scnt: AAAA-kJEQ0RBN0MwRkE2MUUxRjc5REU0RDY3MjY2MzA0ODNDNzYxNkNGNEIwNzE2MDA1Qjc3M0QwRkU5QUQwMkNCQzNBMjJEQTEyM0Y5REJEQzVDNjVFMjIzRjJGQUE3QzVBRkFDRTFFODNFRkZGOUQ2M0IwMTVDNjg0RjQxODBCMDA3NkMwRjY2NEMwN0MwM0QxQjg3MTlCOTJBNzBENEM1NjAzMTdFNDlCRkFEODUwNTM0NEVEM0Q1MjkyNUY2QTNGNkYyOTdERUVDREY2MjkyN0RDNTZEOURCN0JDQzQxOUI3RDRDRjQ5N0ZBQzM0MDE2NXw3AAABkSiQfRDOODVvvZZH7xZV8vOP562LAN7xgdfI77F4x2SXWCMrbSGhWWo7lAoyACOfOEVMvFAfHBhmJwwJEged_mf990xw4hsgi1fmUR5qcyndefyOlw
        //Set-Cookie: dslang=US-EN; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: site=USA; Domain=apple.com; Path=/; Secure; HttpOnly
        //Pragma: no-cache
        //Expires: Thu, 01 Jan 1970 00:00:00 GMT
        //Cache-Control: no-cache
        //Cache-Control: no-store
        //X-Apple-ID-Session-Id: BDCDA7C0FA61E1F79DE4D6726630483C7616CF4B0716005B773D0FE9AD02CBC3A22DA123F9DBDC5C65E223F2FAA7C5AFACE1E83EFFF9D63B015C684F4180B0076C0F664C07C03D1B8719B92A70D4C560317E49BFAD8505344ED3D52925F6A3F6F297DEECDF62927DC56D9DB7BCC419B7D4CF497FAC340165
        //X-Apple-Auth-Attributes: PdjJXQD0biPB+lpqszWKcxA6YfN82wXuujeflp0AdOMXVA/m3rB/wG5ZkY2Y/70l8zLlT+rqDEpC1IsOlLfbThShi4U+qyi0dd7ulQDqO/pGB3LT7XGQWtXTaGN74ZZ0bftoHEKcW8aX4Z7JMAWKpDkGSoqz4KGOIOpZz1PI9unSCgqMphFP3Rxzx5SVySzHKdFTvXbzqb5S+nT+oPdZuzUOeLqMbvMP2Lrd9diBhUfEPsSglPMsw9nl5UStkd5SIvYtACOfOEsDkP0=
        //Set-Cookie: myacinfo=DAWTKNV323952cf8084a204fb20ab2508441a07d02d3cf2a5fe1b885c3d57baeca8e81c12a2a6cd34c0b9c07f33ad8a13a6db883cb9c246246190eceb7e183ddca2fc7f8a836b59ce64af3d20c6e331da2b923f50ce130512c131a41894c20cb137dea6b7c1372131004fe801bfc3e63c96fda9c3facfd693d5bd7174544c65da78e0a4c01b12747cf5c389f7b11e509b4f153504fc91dbb512d5360767029810466a6eb9ca12f28f94eba28fbdc6f9ab949dc343e8296ac60822ce32bc8223cc66ea98392442cd687b1dabc807a37cde1ced0445011a4fae1a4b5f9edc9db1b5711c79c599c9b871e2f8f7f5478b254a0348e9aa219dfaafd75660063f3f63c2754a4bd4645866d752047336d49ab57540ad8abe23022ff5006180dc9015cd81e933d8349fd8f16f1ceb01fecde486a8eb7c9b90b3280db3af7a7efb57403ea86fba17e4ab1f8ee6fd7d354767bbbce53780054021f107833c0273f54cc3604f47e1010cd132dc4bb239f02bc86961bb242c2c8a4f5bdc6ffa6f08043b7bdee4a682101aa2692b82249840b51fe1b9533788c58cc98899a821d64634b35b4c1d6ceb27bbb33a02a9ff7b54a709cc515abae554c35ef296fc55391c3ed823aa2b56ede46397286ab2962d963aa4943ba334fbbe14934beea2cb0688dc22e804ef3b5fd0596e695fb8aad7d7af101a1d78131a527f590203c2be85ff9a1f6e2d922ff2b115de9445c963ed5eaa1a3704939b8a38c372d1909884e1d091d792fc6069fbfa4f8e2d8bbc9440f6a72eefdb99bf54e2ed7b8821ddec257c7b5116382f057baa4c20555ea4b132f2a6c5f976b79308bb6de80b74a449facae683c623651d1c70326eacce418d1c9470d0b8bd6a1d7ab2c30325c04a9ad89503e90220c61040e0b9972585a47V3; Domain=apple.com; Path=/; Secure; HttpOnly
        //X-Apple-ID-Account-Country: CHN
    }
}
