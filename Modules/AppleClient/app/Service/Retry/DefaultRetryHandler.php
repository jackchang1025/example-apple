<?php

namespace Modules\AppleClient\Service\Retry;

use Modules\AppleClient\Service\Apple;
use Saloon\Http\Request;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Exceptions\Request\FatalRequestException;

class DefaultRetryHandler implements RetryHandlerInterface
{
    public function __construct(
        protected Apple $apple,
    ) {
    }

    /**
     * 检查是否为连接异常
     */
    private function isConnectionException($exception): bool
    {
        return $exception instanceof \Saloon\Exceptions\Request\FatalRequestException;
    }

    public function __invoke(FatalRequestException|RequestException $exception, Request $request): bool
    {
        if ($this->isConnectionException($exception) && $this->apple->getProxy()?->isProxyEnabled()) {
            $this->apple->getProxy()?->refreshProxy();

            return true;
        }

        return false;
    }
}
