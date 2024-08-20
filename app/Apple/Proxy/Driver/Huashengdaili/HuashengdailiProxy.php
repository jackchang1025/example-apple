<?php

namespace App\Apple\Proxy\Driver\Huashengdaili;

use App\Apple\Proxy\Driver\ProxyModeFactory;
use App\Apple\Proxy\Exception\ProxyException;
use App\Apple\Proxy\Exception\ProxyModelNotFoundException;
use App\Apple\Proxy\Option;
use App\Apple\Proxy\Proxy;
use App\Apple\Proxy\ProxyConfiguration;
use App\Apple\Proxy\ProxyInterface;
use App\Apple\Proxy\ProxyModeInterface;
use App\Apple\Proxy\ProxyResponse;
use App\Apple\Service\Client\ClientFactory;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * 代理类
 *
 * 该类负责处理华盛代理的获取和缓存逻辑
 */
class HuashengdailiProxy extends Proxy implements ProxyInterface
{
    /**
     * @param ClientFactory $httpFactory
     * @param ProxyConfiguration $config
     * @param ProxyModeFactory $modeFactory
     * @throws BindingResolutionException
     * @throws ProxyModelNotFoundException
     */
    public function __construct(protected ClientFactory $httpFactory,protected ProxyConfiguration $config,protected ProxyModeFactory $modeFactory)
    {
        $this->mode = $this->modeFactory->createMode($this->config);
    }

    public function getMode(): ProxyModeInterface
    {
        return $this->mode;
    }

    /**
     * 获取代理
     *
     * @param Option $option 选项
     * @return ProxyResponse
     * @throws ProxyException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Throwable
     */
    public function getProxy(Option $option): ProxyResponse
    {
        $account = $option->get('session');

        // 如果没有指定账号,直接获取新代理
        if (!$account) {
            return $this->mode->getProxy($option);
        }

        $cacheKey = $this->getCacheKey($account);

        // 尝试从缓存获取代理
        if ($cachedProxy = $this->getCachedProxy($cacheKey)) {
            return $cachedProxy;
        }

        // 获取新代理并缓存
        return $this->getAndCacheNewProxy($option, $cacheKey);
    }

    /**
     * 生成缓存键
     *
     * @param string $account
     * @return string
     */
    protected function getCacheKey(string $account): string
    {
        return 'huashengdaili_proxy_' . $account;
    }

    /**
     * 从缓存获取代理
     *
     * @param string $cacheKey
     * @return ProxyResponse|null
     * @throws InvalidArgumentException|\Psr\SimpleCache\InvalidArgumentException
     */
    protected function getCachedProxy(string $cacheKey): ?ProxyResponse
    {
        if ($cachedProxy = Cache::get($cacheKey)) {
            $proxyResponse = new ProxyResponse($cachedProxy);
            if ($proxyResponse->getTimeToExpire() >= 30) {
                return $proxyResponse;
            }
            Cache::delete($cacheKey);
        }
        return null;
    }

    /**
     * 获取新代理并缓存
     *
     * @param Option $option
     * @param string $cacheKey
     * @return ProxyResponse
     * @throws ProxyException
     * @throws \Throwable
     */
    protected function getAndCacheNewProxy(Option $option, string $cacheKey): ProxyResponse
    {
        $proxyResponse = parent::getProxy($option);
        Cache::put($cacheKey, $proxyResponse->all(), $proxyResponse->getTimeToExpire());
        return $proxyResponse;
    }
}
