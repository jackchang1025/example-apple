<?php

namespace App\Apple\Proxy;

use App\Apple\Proxy\Exception\ProxyException;
use Illuminate\Support\Facades\Http;

abstract class Proxy implements ProxyInterface
{
    /**
     * 代理验证URL
     */
    protected const string VALIDATION_URL = 'https://appleid.apple.com/bootstrap/portal';

    /**
     * 代理验证超时时间（秒）
     */
    public const int VALIDATION_TIMEOUT = 5;

    /**
     * 最大重试次数
     */
    public const int MAX_RETRIES = 5;

    /**
     * 重试延迟（毫秒）
     */
    public const int RETRY_DELAY = 100;

    /**
     * @var ProxyModeInterface 代理模式实例
     */
    protected ProxyModeInterface $mode;

    abstract protected function getMode(): ProxyModeInterface;

    public function getProxyIp(ProxyResponse $proxyResponse): string
    {
        return $this->getMode()->getProxyIp($proxyResponse);
    }

    /**
     * @throws \Throwable
     * @throws ProxyException
     */
    public function getProxy(Option $option): ProxyResponse
    {
        return retry(self::MAX_RETRIES, function () use ($option) {
            return $this->attemptGetProxy($option);
        }, self::RETRY_DELAY);
    }

    /**
     * @param Option $option
     * @return ProxyResponse
     * @throws ProxyException
     */
    protected function attemptGetProxy(Option $option): ProxyResponse
    {
        $proxyResponse =  $this->getMode()->getProxy($option);

        if (!$this->validateProxy($proxyResponse)){
            throw new ProxyException('Proxy validation failed');
        }

        return $proxyResponse;
    }

    /**
     * 验证代理
     *
     * @param ProxyResponse $proxy 代理响应
     * @return bool
     */
    protected function validateProxy(ProxyResponse $proxy): bool
    {
        try {
            return Http::timeout(self::VALIDATION_TIMEOUT)
                ->withOptions([
                    'proxy' => $proxy->getUrl(),
                    'verify' => false,
                ])
                ->get(self::VALIDATION_URL)
                ->successful();

        } catch (\Exception $e) {
            return false;
        }
    }
}
