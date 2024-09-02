<?php

namespace App\Http\Integrations\IpConnector\Requests;

use App\Http\Integrations\IpConnector\IpaddressRequest;
use App\Http\Integrations\IpConnector\Responses\IpResponse;
use App\Http\Integrations\IpConnector\Responses\PconLineResponse;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class PconLineRequest extends IpaddressRequest
{
    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    public function __construct(public string $ip)
    {
    }

    public function defaultQuery(): array
    {

        return[
            'ip' => $this->ip,
        ];
    }

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return 'https://whois.pconline.com.cn/ipJson.jsp';
    }

    public function defaultHeaders (): array
    {
        return [
            'accept'=>'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'accept-encoding'=>'gzip, deflate, br, zstd',
            'accept-language'=>'en,zh-CN;q=0.9,zh;q=0.8',
            'cache-control'=>'max-age=0',
            'priority'=>'u=0, i',
            'sec-ch-ua'=>'"Chromium";v="128", "Not;A=Brand";v="24", "Google Chrome";v="128"',
            'sec-ch-ua-mobile'=>'?0',
            'sec-ch-ua-platform'=>'"Windows"',
            'sec-fetch-dest'=>'document',
            'sec-fetch-mode'=>'navigate',
            'sec-fetch-site'=>'none',
            'sec-fetch-user'=>'?1',
            'upgrade-insecure-requests'=>'1',
            'user-agent'=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36'
        ];
    }

    function extractJsonFromString(Response $response):IpResponse
    {

        // 获取原始响应内容
        $rawContent = $response->body();

        // 将内容从GB2312（或GBK）转换为UTF-8
        $utf8Content = mb_convert_encoding($rawContent, 'UTF-8', 'GB2312,GBK');

        // 使用正则表达式匹配 JSON 数据
        if (preg_match('/IPCallBack\((.*?)\);/', $utf8Content, $matches)) {
            $jsonString = $matches[1];

            // 解析 JSON 字符串
            $data = json_decode($jsonString, true);

            // 检查是否成功解析
            if (json_last_error() === JSON_ERROR_NONE) {
                return new PconLineResponse($data);
            }
        }

        return new PconLineResponse();
    }
}
