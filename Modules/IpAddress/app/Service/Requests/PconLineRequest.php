<?php

namespace Modules\IpAddress\Service\Requests;

use JsonException;
use Modules\IpAddress\Service\Exception\IpLookupException;
use Modules\IpAddress\Service\Responses\IpResponse;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class PconLineRequest extends Request
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

        return [
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

    public function defaultHeaders(): array
    {
        return [
            'accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'accept-encoding' => 'gzip, deflate, br, zstd',
            'accept-language' => 'en,zh-CN;q=0.9,zh;q=0.8',
            'user-agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        ];
    }

    /**
     * @param Response $response
     * @return IpResponse
     * @throws JsonException|IpLookupException
     */
    public function createDtoFromResponse(Response $response): IpResponse
    {

        // 获取原始响应内容
        $rawContent = $response->body();

        // 将内容从GB2312（或GBK）转换为UTF-8
        $utf8Content = mb_convert_encoding($rawContent, 'UTF-8', 'GB2312,GBK');

        // 使用正则表达式匹配 JSON 数据
        if (!preg_match('/IPCallBack\((.*?)\);/', $utf8Content, $matches)) {
            throw new IpLookupException($response, 'Invalid response format from PconLine');
        }

        $jsonString = $matches[1];
        $data       = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new IpLookupException($response, 'Failed to parse JSON response from PconLine');
        }

        //810000 香港
        //820000 澳门
        //710000 台湾
        return new IpResponse([
            'city'      => $data['city'] ?? null,
            'addr'      => $data['addr'] ?? null,
            'ip'        => $data['ip'] ?? null,
            'city_code' => $data['cityCode'] ?? null,
            'pro_code'  => $data['proCode'] ?? null,
            'is_chain' => isset($data['proCode']) && $data['proCode'] !== '999999',
        ]);
    }
}
