<?php

namespace Modules\AppleClient\Service;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\AppleClient\Service\Config\Config;
use Modules\AppleClient\Service\Config\HasConfig;
use Modules\AppleClient\Service\DataConstruct\Account;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Modules\AppleClient\Service\Middleware\MiddlewareInterface;
use Modules\AppleClient\Service\Cookies\CookieJarInterface;
use Modules\AppleClient\Service\Header\HeaderSynchronizeInterface;
use Modules\AppleClient\Service\Retry\RetryHandlerInterface;

class AppleBuilder
{
    private Account $account;
    private Apple $apple;

    use HasConfig;

    /**
     * 构造函数
     */
    public function __construct(
        protected CacheInterface $cache,
        protected Dispatcher $dispatcher,

    ) {
        $this->config = new Config(config('apple'));
    }

    /**
     * 加载所有配置
     */
    private function loadConfigurations(): void
    {
        // 加载 Cookie 配置
//        $this->apple->withCookies($this->loadCookieConfig());

        // 加载 Header 配置
//        $this->apple->withHeaderRepositories($this->loadHeaderConfig());

        // 加载 Logger 配置
        $this->apple->withLogger(app($this->config->get('logger.class')));

        // 加载 Proxy 配置
        $this->apple->withProxy(app($this->config->get('proxy.class')));

        // 加载 Retry 配置
        $this->loadRetryConfig();

        // 加载中间件
        $this->loadMiddlewares();
    }

    /**
     * 加载 Cookie 配置
     */
    private function loadCookieConfig(): CookieJarInterface
    {
        $cookieJar = $this->config->get('cookie.class');
        if (!class_exists($cookieJar)) {
            throw new RuntimeException("Cookie class {$cookieJar} not found");
        }

        $cookie = new $cookieJar(
            cache: $this->cache,
            key: $this->account->getSessionId(),
            ttl: $this->config->get('cookie.ttl')
        );

        if (!$cookie instanceof CookieJarInterface) {
            throw new RuntimeException('Cookie class must implement CookieJarInterface');
        }

        return $cookie;
    }

    /**
     * 加载 Header 配置
     */
    private function loadHeaderConfig(): HeaderSynchronizeInterface
    {
        $headerClass = $this->config->get('header.class');
        if (!class_exists($headerClass)) {
            throw new RuntimeException("Header class {$headerClass} not found");
        }

        $storeClass = $this->config->get('header.store.class');

        if (!class_exists($storeClass)) {
            throw new RuntimeException("Store class {$storeClass} not found");
        }

        // 创建存储实例
        $store = new $storeClass(
            cache: $this->cache,
            key: $this->account->getSessionId(),
            ttl: $this->config->get('header.store.ttl'),
            prx: $this->config->get('header.store.prefix'),
            defaultData: $this->config->get('header.store.defaultData')
        );

        // 创建 Header 同步实例
        $header = new $headerClass($store);

        if (!$header instanceof HeaderSynchronizeInterface) {
            throw new RuntimeException('Header class must implement HeaderSynchronizeInterface');
        }

        return $header;
    }

    /**
     * 加载重试配置
     */
    private function loadRetryConfig(): void
    {
        $handler = $this->config->get('retry.handler');

        if (is_string($handler)) {
            if (!class_exists($handler)) {
                throw new RuntimeException("Retry handler class {$handler} not found");
            }

            $handler = app($handler);

            if (!$handler instanceof RetryHandlerInterface) {
                throw new RuntimeException('Retry handler must implement RetryHandlerInterface');
            }
        }

        $retryCallback = function (FatalRequestException|RequestException $exception, \Saloon\Http\Request $request) use
        (
            $handler
        ) {
            return $handler($exception, $request);
        };

        $this->apple
            ->withTries($this->config->get('retry.tries', 1))
            ->withRetryInterval($this->config->get('retry.retryInterval', 1))
            ->withUseExponentialBackoff($this->config->get('retry.useExponentialBackoff', true))
            ->withHandleRetry($retryCallback);
    }

    /**
     * 加载中间件
     */
    private function loadMiddlewares(): void
    {
        foreach ($this->config->get('middleware') as $name => $middlewareClass) {
            $this->loadMiddleware($name, $middlewareClass);
        }
    }

    /**
     * 加载单个中间件
     */
    private function loadMiddleware(string $name, string $middlewareClass): void
    {
        if (!class_exists($middlewareClass)) {
            throw new RuntimeException("Middleware class {$middlewareClass} not found");
        }

        $middleware = app($middlewareClass);

        if (!$middleware instanceof MiddlewareInterface) {
            throw new RuntimeException("Middleware must implement MiddlewareInterface");
        }

        $this->apple->middleware()
            ->onRequest([$middleware, 'onRequest'], $name)
            ->onResponse([$middleware, 'onResponse'], $name);
    }

    public function build(Account|array|string $account): Apple
    {
        $this->account = $this->resolveAccount($account);

        // 创建基础 Apple 实例，使用配置或默认配置
        $this->apple = new Apple($this->account, $this->config);

        // 注册 Apple 实例
        app()->instance(Apple::class, $this->apple);

        // 加载各组件配置
        $this->loadConfigurations();

        $this->apple->withDispatcher($this->dispatcher);

        return $this->apple;
    }

    /**
     * 解析账号信息
     */
    private function resolveAccount(Account|array|string $account): Account
    {
        if (is_string($account)) {
            if (!$this->cache) {
                throw new RuntimeException('Cache is required when using session ID');
            }
            $account = $this->cache->get($account);
            if (!$account) {
                throw new RuntimeException('Session expired or invalid');
            }
        }

        if ($account instanceof Account) {
            return $account;
        }

        if (is_array($account)) {
            return Account::from($account);
        }

        throw new RuntimeException('Invalid account');
    }
}
