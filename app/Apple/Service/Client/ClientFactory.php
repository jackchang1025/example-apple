<?php

namespace App\Apple\Service\Client;

use App\Apple\Service\Client\Middleware\GlobalMiddlewareInterface;
use App\Apple\Service\Client\Middleware\MiddlewareManager;
use App\Apple\Service\Client\Middleware\RequestMiddlewareInterface;
use App\Apple\Service\Client\Middleware\ResponseMiddlewareInterface;
use Closure;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

/**
 * @mixin MiddlewareManager
 */
class ClientFactory
{
    private const string USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly MiddlewareManager $middlewareManager
    ) {
    }

    public function getMiddlewareManager(): MiddlewareManager
    {
        return $this->middlewareManager;
    }

    public function create(array $options = []): PendingRequest
    {
        $client = $this->http
            ->withUserAgent(self::USER_AGENT)
            ->withOptions($options);

        foreach ($this->middlewareManager->getRequestMiddlewares() as $middleware) {
            $client->withRequestMiddleware($this->ensureCallable($middleware, 'request'));
        }

        foreach ($this->middlewareManager->getResponseMiddlewares() as $middleware) {
            $client->withResponseMiddleware($this->ensureCallable($middleware, 'response'));
        }

        /**
         * @var PendingRequest $client
         */

        return $client;
    }

    private function ensureCallable(mixed $middleware, string $type): callable
    {
        if ($middleware instanceof Closure) {
            return $middleware;
        }

        if ($middleware instanceof RequestMiddlewareInterface || $middleware instanceof ResponseMiddlewareInterface) {
            return [$middleware, '__invoke'];
        }

        if ($middleware instanceof GlobalMiddlewareInterface) {
            return [$middleware, $type];
        }

        if (is_string($middleware) && class_exists($middleware)) {

            $instance = app($middleware);

            if ($instance instanceof GlobalMiddlewareInterface) {
                return [$instance, $type];
            }

            if ($instance instanceof RequestMiddlewareInterface || $instance instanceof ResponseMiddlewareInterface) {
                return $instance;
            }

            if (method_exists($instance, '__invoke')) {
                return [$instance, '__invoke'];
            }
        }

        throw new \InvalidArgumentException('Middleware must be a Closure, an invokable object, a GlobalMiddleware instance, or a class name with an __invoke method or implementing GlobalMiddleware.');
    }

    public function __call($method, $parameters)
    {
        return $this->middlewareManager->{$method}(...$parameters);
    }
}
