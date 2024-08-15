<?php

namespace App\Apple\WebAnalytics;

use Illuminate\Support\Facades\Http;

class IpApi
{
    /**
     * @param string $ip
     * @param string $lang
     * @return Ip
     * @throws \Illuminate\Http\Client\ConnectionException
     */
    function getIpInfo(string $ip,string $lang = 'zh-CN'): Ip
    {
        $response = Http::retry(3,100)
            ->get("http://ip-api.com/json/{$ip}?lang=zh-CN",['lang' => $lang]);

       return new Ip($response->json());
    }
}
