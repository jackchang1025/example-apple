<?php

namespace App\Apple\Service\Client;

use App\Apple\Service\Exception\UnauthorizedException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

class AppleIdClient extends BaseClient
{

    protected function createClient(): Client
    {
       return $this->clientFactory->create(user: $this->user,additionalConfig: [
           'base_uri'              => self::BASEURL_APPLEID,
           'timeout'               => 30,
           'connect_timeout'       => 60,
           'verify'                => false,
           'proxy'                 => $this->getProxyResponse()->getUrl(),  // 添加这行
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
                ],
            ],
        ]);
    }

    /**
     * @return Response
     * @throws GuzzleException
     */
    public function managePrivacyAccept(): Response
    {
        //PUT /account/manage/privacy/accept HTTP/1.1
        //Accept: application/json, text/javascript, */*; q=0.01
        //Accept-Encoding: gzip, deflate, br, zstd
        //Accept-Language: en,zh-CN;q=0.9,zh;q=0.8
        //Connection: keep-alive
        //Content-Length: 0
        //Content-Type: application/json
        //Cookie: dssid2=359a4ccc-4628-4877-aab3-3b780a862304; dssf=1; as_sfa=Mnx1c3x1c3x8ZW5fVVN8Y29uc3VtZXJ8aW50ZXJuZXR8MHwwfDE; pldfltcid=c30f6120e0484dc6b71e6e784a61e337012; pltvcid=undefined; pxro=1; POD=us~en; nn-user-journey=%7B%22value%22%3A%7B%22os%22%3A%2215.0.0%3B%22%2C%22device%22%3A%22WINDOWS%3B%20X86%3B%2064%3B%22%2C%22journey%22%3A%7B%22lastPath%22%3A%22https%3A%2F%2Fsecure5.store.apple.com%2Fshop%2Faccount%2Fhome%22%2C%22stitchPath%22%3A%5B%22no-referrer%22%2C%22%2Fen-us%2F102637%22%2C%22%2F%22%2C%22https%3A%2F%2Fwww.apple.com%2Fus%2Fshop%2Fgoto%2Fbag%22%2C%22https%3A%2F%2Fsecure5.store.apple.com%2Fshop%2Faccount%2Fhome%22%5D%7D%7D%2C%22expiry%22%3A1722790639745%7D; geo=CN; s_cc=true; as_pcts=rCCnITwRN4TNkb6mbNEuEw32q+HXBLjBmr6xKFP_:RTAi4mSxW_qKpZmPmC-YnDyc+ek:ZsvSql2FL+S3ToQhVYTg56CsyGYe04Owbd6oZuIaS5aMoGi6ZxpOd:SWTrCkQaNEaQd_--SgZaifenkKnRwq6J2oCiQwh:5xm645iP6ENZOU; as_dc=ucp4; dslang=US-EN; site=USA; as_rumid=6f4cf224-9877-4547-8ed2-43996e2595b8; idclient=web; itspod=31; s_fid=7D87CF4BB6BF5B1A-3103566EF86D8AF7; aidsp=CD0A6BF0C14242AE47EF38EA5CE6401FC58BFF1DE7DDDEBDA91F9CD627926FF32433B6CC3B941D0C1B53B130ED84A0DBF6076B25E5D71CAC3CC4D4E1D2D861C4C09019B47871C0A4E913298AA28821DB1B893E557F9E918CD7F99D393D7E44F46DD0C2B0705DA9FE4F8E36DCCFA813AAC3350BC37474C6DF; s_sq=%5B%5BB%5D%5D; pt-dm=v1~x~ayvotxez~m~3~n~AOS%3A%20checkout%20%3A%20sign%20in~r~aos%3Aaccount; aid=0785EA31B7D79D84904AB7CBB733E360
        //Host: appleid.apple.com
        //Origin: https://appleid.apple.com
        //Referer: https://appleid.apple.com/
        //Sec-Fetch-Dest: empty
        //Sec-Fetch-Mode: cors
        //Sec-Fetch-Site: same-origin
        //User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36
        //X-Apple-I-FD-Client-Info: {"U":"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36","L":"en","Z":"GMT+08:00","V":"1.1","F":".la44j1e3NlY5BNlY5BSmHACVZXnNA9dMN0HxVaWvMurJhBR.uMp4UdHz13Nl_jV2pNk0ug9WJ3veSAupyMgzB3NlY5BNp55BNlan0Os5Apw.0I4"}
        //X-Apple-I-TimeZone: Asia/Shanghai
        //X-Apple-ID-Session-Id: CD0A6BF0C14242AE47EF38EA5CE6401FC58BFF1DE7DDDEBDA91F9CD627926FF32433B6CC3B941D0C1B53B130ED84A0DBF6076B25E5D71CAC3CC4D4E1D2D861C4C09019B47871C0A4E913298AA28821DB1B893E557F9E918CD7F99D393D7E44F46DD0C2B0705DA9FE4F8E36DCCFA813AAC3350BC37474C6DF
        //X-Apple-OAuth-Context: Oo9rvSiVSU/oLnmTrKXFj/0XanM/Dj7dz8SZfPSYAQG0i8tnk/FX5yJkE8vNcW0PJEfeVDJYA0Gmi9kzaE6WVA9aLJn199DC1DTwMAQXfFkPcbPxzxyPLAsBFqMvOZqkVQ9P8z0/xxzoMklcBu/C6eTnuuMPeetR4uQuSECg7rkZD0U0BEChk5kQunMewPoFXeTAIUwztEuiehSmk4llyT38ACYAAySa7qA=
        //X-Apple-Session-Token: i5LMrBJQvX0FA0k7ifaj05MzDm2R2NRzY/ZbFqp9W0n/lbq3OEwLg34Gv0hEqswR82X3uZSJV05/XGcgbstX+KAriiA069D8Rio33TrJy19aa2KYp4FpCiR9cSt6zqMjJWW2cO0FHxIj0Jk8GqCoN/T1JAbWVaK8enRdVR2fohxJ8RNkdy+oW1VhFSSC1Qi1jky2ciCk74tUsHcf+OHDJssHsuEVZwj9sgD1MYu0pSYnPFs5I7gOzQ/SEFZy5Q5brJLSuPByfiGtC+m630p+czpKCztdg+8/gc2kBkTNZEBkUvadYns4CI6uOYpL1xWcC8OO64DIb3iAhO50Q/2fTcQusutGlB81A7+TGBssUtQbHYynV2JwuzxcI5xS3tMPOrdMZJONwhh+PhcKJkrradejTKtcQX/u1fVTSoYwd9ysvL/x1+xY4Tj1ZlPviq1WuIoE52M60k02pvYz+25tcmjAhESgrxy2b9+7VufzjKjbFTG1qo7kNnloGsTqQVl7PEKIDowZl/8cLwOdwHnCW4IdtoLlGCtxY3LGg/BrLtLcYu9BfthcZDzp9ZiuqTbzD1jNWetbeuy6Kl//WFXUMF3IdFIkezQVu61eVW5zLrJfEYEaEXopvnsw0V92zSjyZgHZjX0GjqAyIgIvKm8jEO+mWKu7ais/Oxm/lLzCxQHpXxfGqnEH3/tN6vChVGxMKAAZtByJHfXFUWkhoHWvi2kTAWHE+Lio/LOjVSxIi4MA9kcS19CwPmRRoDXonhDjTM85G8JF8qGljYYjnPy9teHKIUAqXfBEEI2agwgZaIvhMQdrUQFjkUPZk1AmVn3w1b+HG4er5mu1RjyXjmAPV/jAMDs9kawHKaj40DkmQyX980q1oOT9KJxa2WMcqJn/AiiNMORbrSFQd3be/63W+EfX7/JCwO1a170mAUxVqZUHIUHiflj2S8DzrGz0kY63OokvO/u9AnQaBV6Mrt/6Dagqnb1f3Z8KyG+6NV7rNJCdjUrCfHkD7yhGFLRT93Utw9+Mkp0QETo6TF7XvDougo79uGMpTP3LdEmsiPir14giuiGmBNCFGNegQJsoy7B5Y+gkhPC5fewj4Z+A6d6aNSI2Bpt3ns8yxH0MXjPB2LuGlB0fXzm/o/RHx4JEL+fRK55QS1KViJtzKDQOXE6XMq5nzx6tritNz8FpCV/exZvTEZFU/Y+9PmvjYqXvxK3mDj4AJPbz9kOKcg==
        //X-Apple-Skip-Repair-Attributes: []
        //X-Apple-Widget-Key: af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3
        //X-Requested-With: XMLHttpRequest
        //scnt: AAAA-0NEMEE2QkYwQzE0MjQyQUU0N0VGMzhFQTVDRTY0MDFGQzU4QkZGMURFN0REREVCREE5MUY5Q0Q2Mjc5MjZGRjMyNDMzQjZDQzNCOTQxRDBDMUI1M0IxMzBFRDg0QTBEQkY2MDc2QjI1RTVENzFDQUMzQ0M0RDRFMUQyRDg2MUM0QzA5MDE5QjQ3ODcxQzBBNEU5MTMyOThBQTI4ODIxREIxQjg5M0U1NTdGOUU5MThDRDdGOTlEMzkzRDdFNDRGNDZERDBDMkIwNzA1REE5RkU0RjhFMzZEQ0NGQTgxM0FBQzMzNTBCQzM3NDc0QzZERnwyMAAAAZEoi1KcG5SuA81rl7VMc9_ORFkpzrnwWcMISv5aEAkSTkmsoKevWxHqpLFfhwAk9vPwKDoOQX7DvPQja4MMmb3sil3jnTaHShEwxvA0qoWiSwTRbM8
        //sec-ch-ua: "Not)A;Brand";v="99", "Google Chrome";v="127", "Chromium";v="127"
        //sec-ch-ua-mobile: ?0
        //sec-ch-ua-platform: "Windows"
        return $this->request('OPTIONS', '/account/manage/privacy/accept',[
            RequestOptions::HEADERS => [
                'X-Apple-Widget-Key'          => $this->user->getConfig()?->getServiceKey() ?? '',
                'X-Apple-ID-Session-Id'   => $this->cookieJar->getCookieByName('aidsp')->getValue(),
                'X-Apple-OAuth-Context' => $this->user->getHeader('X-Apple-OAuth-Context') ?? '',
                'X-Apple-Session-Token' => $this->user->getHeader('X-Apple-Session-Token') ?? '',
            ],
        ]);

        //HTTP/1.1 200
        //Server: Apple
        //Date: Tue, 06 Aug 2024 16:25:41 GMT
        //Content-Type: application/json;charset=UTF-8
        //Transfer-Encoding: chunked
        //Connection: keep-alive
        //X-Apple-I-Request-ID: 855e89b9-5410-11ef-b366-bd60bf258572
        //X-BuildVersion: R14_6
        //X-FRAME-OPTIONS: DENY
        //X-Content-Type-Options: nosniff
        //X-XSS-Protection: 1; mode=block
        //Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
        //Content-Security-Policy: default-src 'self' ; connect-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com https://api.apple-cloudkit.com https://feedbackws.apple-cloudkit.com https://*.icloud-content.com https://*.icloud-content.com.cn ; font-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com ; frame-src 'self' https://idmsa.apple.com https://signin.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://familyws.icloud.apple.com  https://apps.apple.com ; img-src 'self' https://www.apple.com https://appleid.cdn-apple.com data: https://*.mzstatic.com https://appleid.apple.com https://*.icloud.com ; media-src data: ; object-src 'none' ; script-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://signin.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://appleid.apple.com ; style-src 'unsafe-inline' 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com ;
        //Referrer-Policy: origin
        //scnt: AAAA-0NEMEE2QkYwQzE0MjQyQUU0N0VGMzhFQTVDRTY0MDFGQzU4QkZGMURFN0REREVCREE5MUY5Q0Q2Mjc5MjZGRjMyNDMzQjZDQzNCOTQxRDBDMUI1M0IxMzBFRDg0QTBEQkY2MDc2QjI1RTVENzFDQUMzQ0M0RDRFMUQyRDg2MUM0QzA5MDE5QjQ3ODcxQzBBNEU5MTMyOThBQTI4ODIxREIxQjg5M0U1NTdGOUU5MThDRDdGOTlEMzkzRDdFNDRGNDZERDBDMkIwNzA1REE5RkU0RjhFMzZEQ0NGQTgxM0FBQzMzNTBCQzM3NDc0QzZERnwyMQAAAZEokJiDwcfeNGvZs2lR2cdDHEAzmunC5QbiFSuyqFFewPV_oLxk_fW6BFh8kgAk5KbaYbAmorvt2b-q2iUEx8dwD5RFu5QhDU6v9lC7fccSwK7iAVc
        //Set-Cookie: dslang=US-EN; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: site=USA; Domain=apple.com; Path=/; Secure; HttpOnly
        //Pragma: no-cache
        //Expires: Thu, 01 Jan 1970 00:00:00 GMT
        //Cache-Control: no-cache
        //Cache-Control: no-store
        //X-Apple-Session-Token: UDzWOFsLGB7SEZLEU7Pgh9vdp8w1WGXWYxo+8zDpXZwdxUG2ddEKVSbtXkuLrULP0LzFlxk6CVfKBgth/aFMIlune4O4OZYMRPMSjql/L+5nAmnz6G0EdqRTaNl3oS5l8GRnHrxTa+iU4Jl1Ar9d1/grHm/8nt82x7NbD3PPwK+jCmL2mPN3yt6f4/zqvu9VjPcDw7uOFtHMAA+xpWRLIOFVGJsvNWP47I1yWY1SUcJk7jaKC2AK1CL7UG6JrofTOLnK701hcFt5Rmnoi3fRoUBWY77StmTvL3NfK6yWQ2CDfVyfJZTVVs1R5AYakGvrT5omkDTFUk9DjQ0U/FAnJZgQgxonOTgEVgydluXGkciNf0O5VXmkhOXtcPDPjyl7sl6P2Tt2CpPm5NTJmJsSUQTUIG3chY5gYRAptMEdwyNlh0TLRMNDdQtdfcVmFkIXr/++8oKTIMyEX8kR1jFRoJKoY6fTmICxHDARqnW1MkciM9TSG4yzMu6uix6/ScUJ+kJxqfUWir9ruK3aYcO/VZV4e9+QRXKG2JF+HFh6QQmqkrv1dhXPwBmW2KKqLBzpOXqq8onloLteo2yikNfnefnRTgh+0O+ZbqYdZfowxOD0SX/Uku/yve2+U7WhtquLQROAIygGsn69yJMgR+jeZyjHuA+/x/Xv0ubzbwLtMm7i4KMr4kJcx5DSEUvQ+pGalgUA9MHI3LcDaeum0MPnp7AwJQFO89Ta3T7o1CiL/Gd5z0oQgEP7RuzQPa3cuEv+mkRhIFpCZUlLPEVz46t+KM2I9xWHtP1oCO0Zik8FTZUHKQRJzpLJ2qO6u3ja6F+iQ5hMORv1s1U2iBF0zRX+mtcnBTXY0LEjC9z4kz7JlWVYBA0LjL5wqy5skvh058lmVcdZMv+hsrbGHwUPb4ZSiHP1vL1HdrAVyZx3CEjDZxSuYOCJDJpuwi7KgvjNWimzW33p8rGbDIM/YV3P4G48/UM+f+wXbM7Ktb8ACADCuuS84VHjxRYGKRIVw/13d8HEwzsvwcbyBJphQyQVAlehzqKf4rHwh9EwziH8WQhmYf5W/O6MYZJ6/FK5jvb9vRkoKeNtUZkd8FGsg/RXqQOM0Bcl5HL3eyxBE5Dq9rekTyW2wvWGhd/7hT9Uag4Y0j9lQUiQ6K04Yvd6JghvIq7+3ZKvxns1Ii1rw3qErJhb/Jto1mhDqjkdMDraRUQyRpjQvxMAJOSm+J59fQ==
        //Cache-Control: no-store
        //vary: accept-encoding
        //Content-Encoding: gzip
        //Content-Language: en-US-x-lvariant-USA
        //Host: appleid.apple.com
    }

    /**
     * @return Response
     * @throws GuzzleException
     */
    public function manageRepairOptions(): Response
    {
        // //HTTP/1.1 200
        //        //Server: Apple
        //        //Date: Tue, 06 Aug 2024 16:19:55 GMT
        //        //Content-Type: application/json;charset=UTF-8
        //        //Transfer-Encoding: chunked
        //        //Connection: keep-alive
        //        //X-Apple-I-Request-ID: b763acd9-540f-11ef-b4c1-7f6d36115904
        //        //X-BuildVersion: R14_6
        //        //X-FRAME-OPTIONS: DENY
        //        //X-Content-Type-Options: nosniff
        //        //X-XSS-Protection: 1; mode=block
        //        //Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
        //        //Content-Security-Policy: default-src 'self' ; connect-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com https://api.apple-cloudkit.com https://feedbackws.apple-cloudkit.com https://*.icloud-content.com https://*.icloud-content.com.cn ; font-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com ; frame-src 'self' https://idmsa.apple.com https://signin.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://familyws.icloud.apple.com  https://apps.apple.com ; img-src 'self' https://www.apple.com https://appleid.cdn-apple.com data: https://*.mzstatic.com https://appleid.apple.com https://*.icloud.com ; media-src data: ; object-src 'none' ; script-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://signin.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://appleid.apple.com ; style-src 'unsafe-inline' 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com ;
        //        //Referrer-Policy: origin
        //        //scnt: AAAA-0NEMEE2QkYwQzE0MjQyQUU0N0VGMzhFQTVDRTY0MDFGQzU4QkZGMURFN0REREVCREE5MUY5Q0Q2Mjc5MjZGRjMyNDMzQjZDQzNCOTQxRDBDMUI1M0IxMzBFRDg0QTBEQkY2MDc2QjI1RTVENzFDQUMzQ0M0RDRFMUQyRDg2MUM0QzA5MDE5QjQ3ODcxQzBBNEU5MTMyOThBQTI4ODIxREIxQjg5M0U1NTdGOUU5MThDRDdGOTlEMzkzRDdFNDRGNDZERDBDMkIwNzA1REE5RkU0RjhFMzZEQ0NGQTgxM0FBQzMzNTBCQzM3NDc0QzZERnwyMAAAAZEoi1KcG5SuA81rl7VMc9_ORFkpzrnwWcMISv5aEAkSTkmsoKevWxHqpLFfhwAk9vPwKDoOQX7DvPQja4MMmb3sil3jnTaHShEwxvA0qoWiSwTRbM8
        //        //Set-Cookie: dslang=US-EN; Domain=apple.com; Path=/; Secure; HttpOnly
        //        //Set-Cookie: site=USA; Domain=apple.com; Path=/; Secure; HttpOnly
        //        //Pragma: no-cache
        //        //Expires: Thu, 01 Jan 1970 00:00:00 GMT
        //        //Cache-Control: no-cache
        //        //Cache-Control: no-store
        //        //X-Apple-Session-Token: i5LMrBJQvX0FA0k7ifaj05MzDm2R2NRzY/ZbFqp9W0n/lbq3OEwLg34Gv0hEqswR82X3uZSJV05/XGcgbstX+KAriiA069D8Rio33TrJy19aa2KYp4FpCiR9cSt6zqMjJWW2cO0FHxIj0Jk8GqCoN/T1JAbWVaK8enRdVR2fohxJ8RNkdy+oW1VhFSSC1Qi1jky2ciCk74tUsHcf+OHDJssHsuEVZwj9sgD1MYu0pSYnPFs5I7gOzQ/SEFZy5Q5brJLSuPByfiGtC+m630p+czpKCztdg+8/gc2kBkTNZEBkUvadYns4CI6uOYpL1xWcC8OO64DIb3iAhO50Q/2fTcQusutGlB81A7+TGBssUtQbHYynV2JwuzxcI5xS3tMPOrdMZJONwhh+PhcKJkrradejTKtcQX/u1fVTSoYwd9ysvL/x1+xY4Tj1ZlPviq1WuIoE52M60k02pvYz+25tcmjAhESgrxy2b9+7VufzjKjbFTG1qo7kNnloGsTqQVl7PEKIDowZl/8cLwOdwHnCW4IdtoLlGCtxY3LGg/BrLtLcYu9BfthcZDzp9ZiuqTbzD1jNWetbeuy6Kl//WFXUMF3IdFIkezQVu61eVW5zLrJfEYEaEXopvnsw0V92zSjyZgHZjX0GjqAyIgIvKm8jEO+mWKu7ais/Oxm/lLzCxQHpXxfGqnEH3/tN6vChVGxMKAAZtByJHfXFUWkhoHWvi2kTAWHE+Lio/LOjVSxIi4MA9kcS19CwPmRRoDXonhDjTM85G8JF8qGljYYjnPy9teHKIUAqXfBEEI2agwgZaIvhMQdrUQFjkUPZk1AmVn3w1b+HG4er5mu1RjyXjmAPV/jAMDs9kawHKaj40DkmQyX980q1oOT9KJxa2WMcqJn/AiiNMORbrSFQd3be/63W+EfX7/JCwO1a170mAUxVqZUHIUHiflj2S8DzrGz0kY63OokvO/u9AnQaBV6Mrt/6Dagqnb1f3Z8KyG+6NV7rNJCdjUrCfHkD7yhGFLRT93Utw9+Mkp0QETo6TF7XvDougo79uGMpTP3LdEmsiPir14giuiGmBNCFGNegQJsoy7B5Y+gkhPC5fewj4Z+A6d6aNSI2Bpt3ns8yxH0MXjPB2LuGlB0fXzm/o/RHx4JEL+fRK55QS1KViJtzKDQOXE6XMq5nzx6tritNz8FpCV/exZvTEZFU/Y+9PmvjYqXvxK3mDj4AJPbz9kOKcg==
        //        //Cache-Control: no-store
        //        //vary: accept-encoding
        //        //Content-Encoding: gzip
        //        //Content-Language: en-US-x-lvariant-USA
        //        //Host: appleid.apple.com

        return $this->request('GET', '/account/manage/repair/options',[
            RequestOptions::HEADERS => [
                'X-Apple-Widget-Key'          => $this->user->getConfig()?->getServiceKey() ?? '',
                'X-Apple-ID-Session-Id'   => $this->cookieJar->getCookieByName('aidsp')->getValue(),
                'X-Apple-OAuth-Context' => $this->user->getHeader('X-Apple-OAuth-Context') ?? '',
                'X-Apple-Session-Token' => $this->user->getHeader('X-Apple-Repair-Session-Token') ?? '',
            ],
        ]);

        //HTTP/1.1 200
        //Server: Apple
        //Date: Tue, 06 Aug 2024 16:19:55 GMT
        //Content-Type: application/json;charset=UTF-8
        //Transfer-Encoding: chunked
        //Connection: keep-alive
        //X-Apple-I-Request-ID: b763acd9-540f-11ef-b4c1-7f6d36115904
        //X-BuildVersion: R14_6
        //X-FRAME-OPTIONS: DENY
        //X-Content-Type-Options: nosniff
        //X-XSS-Protection: 1; mode=block
        //Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
        //Content-Security-Policy: default-src 'self' ; connect-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com https://api.apple-cloudkit.com https://feedbackws.apple-cloudkit.com https://*.icloud-content.com https://*.icloud-content.com.cn ; font-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com ; frame-src 'self' https://idmsa.apple.com https://signin.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://familyws.icloud.apple.com  https://apps.apple.com ; img-src 'self' https://www.apple.com https://appleid.cdn-apple.com data: https://*.mzstatic.com https://appleid.apple.com https://*.icloud.com ; media-src data: ; object-src 'none' ; script-src 'self' https://www.apple.com https://appleid.cdn-apple.com https://idmsa.apple.com https://signin.apple.com https://gsa.apple.com https://idmsa.apple.com.cn https://appleid.apple.com ; style-src 'unsafe-inline' 'self' https://www.apple.com https://appleid.cdn-apple.com https://appleid.apple.com ;
        //Referrer-Policy: origin
        //scnt: AAAA-0NEMEE2QkYwQzE0MjQyQUU0N0VGMzhFQTVDRTY0MDFGQzU4QkZGMURFN0REREVCREE5MUY5Q0Q2Mjc5MjZGRjMyNDMzQjZDQzNCOTQxRDBDMUI1M0IxMzBFRDg0QTBEQkY2MDc2QjI1RTVENzFDQUMzQ0M0RDRFMUQyRDg2MUM0QzA5MDE5QjQ3ODcxQzBBNEU5MTMyOThBQTI4ODIxREIxQjg5M0U1NTdGOUU5MThDRDdGOTlEMzkzRDdFNDRGNDZERDBDMkIwNzA1REE5RkU0RjhFMzZEQ0NGQTgxM0FBQzMzNTBCQzM3NDc0QzZERnwyMAAAAZEoi1KcG5SuA81rl7VMc9_ORFkpzrnwWcMISv5aEAkSTkmsoKevWxHqpLFfhwAk9vPwKDoOQX7DvPQja4MMmb3sil3jnTaHShEwxvA0qoWiSwTRbM8
        //Set-Cookie: dslang=US-EN; Domain=apple.com; Path=/; Secure; HttpOnly
        //Set-Cookie: site=USA; Domain=apple.com; Path=/; Secure; HttpOnly
        //Pragma: no-cache
        //Expires: Thu, 01 Jan 1970 00:00:00 GMT
        //Cache-Control: no-cache
        //Cache-Control: no-store
        //X-Apple-Session-Token: i5LMrBJQvX0FA0k7ifaj05MzDm2R2NRzY/ZbFqp9W0n/lbq3OEwLg34Gv0hEqswR82X3uZSJV05/XGcgbstX+KAriiA069D8Rio33TrJy19aa2KYp4FpCiR9cSt6zqMjJWW2cO0FHxIj0Jk8GqCoN/T1JAbWVaK8enRdVR2fohxJ8RNkdy+oW1VhFSSC1Qi1jky2ciCk74tUsHcf+OHDJssHsuEVZwj9sgD1MYu0pSYnPFs5I7gOzQ/SEFZy5Q5brJLSuPByfiGtC+m630p+czpKCztdg+8/gc2kBkTNZEBkUvadYns4CI6uOYpL1xWcC8OO64DIb3iAhO50Q/2fTcQusutGlB81A7+TGBssUtQbHYynV2JwuzxcI5xS3tMPOrdMZJONwhh+PhcKJkrradejTKtcQX/u1fVTSoYwd9ysvL/x1+xY4Tj1ZlPviq1WuIoE52M60k02pvYz+25tcmjAhESgrxy2b9+7VufzjKjbFTG1qo7kNnloGsTqQVl7PEKIDowZl/8cLwOdwHnCW4IdtoLlGCtxY3LGg/BrLtLcYu9BfthcZDzp9ZiuqTbzD1jNWetbeuy6Kl//WFXUMF3IdFIkezQVu61eVW5zLrJfEYEaEXopvnsw0V92zSjyZgHZjX0GjqAyIgIvKm8jEO+mWKu7ais/Oxm/lLzCxQHpXxfGqnEH3/tN6vChVGxMKAAZtByJHfXFUWkhoHWvi2kTAWHE+Lio/LOjVSxIi4MA9kcS19CwPmRRoDXonhDjTM85G8JF8qGljYYjnPy9teHKIUAqXfBEEI2agwgZaIvhMQdrUQFjkUPZk1AmVn3w1b+HG4er5mu1RjyXjmAPV/jAMDs9kawHKaj40DkmQyX980q1oOT9KJxa2WMcqJn/AiiNMORbrSFQd3be/63W+EfX7/JCwO1a170mAUxVqZUHIUHiflj2S8DzrGz0kY63OokvO/u9AnQaBV6Mrt/6Dagqnb1f3Z8KyG+6NV7rNJCdjUrCfHkD7yhGFLRT93Utw9+Mkp0QETo6TF7XvDougo79uGMpTP3LdEmsiPir14giuiGmBNCFGNegQJsoy7B5Y+gkhPC5fewj4Z+A6d6aNSI2Bpt3ns8yxH0MXjPB2LuGlB0fXzm/o/RHx4JEL+fRK55QS1KViJtzKDQOXE6XMq5nzx6tritNz8FpCV/exZvTEZFU/Y+9PmvjYqXvxK3mDj4AJPbz9kOKcg==
        //Cache-Control: no-store
        //vary: accept-encoding
        //Content-Encoding: gzip
        //Content-Language: en-US-x-lvariant-USA
        //Host: appleid.apple.com

        //
        //{
        //    "type": "hsa2",
        //    "repairAttribute": "privacy_consent",
        //    "requiredSteps": [
        //        "privacy_consent"
        //    ],
        //    "allowiCloudAccount": false,
        //    "repairItem": "privacyConsent",
        //    "phoneNumberRequirementGracePeriodEnded": false
        //}
    }

    //appleauth/auth/repair/complete


}
