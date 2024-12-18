<?php

namespace Modules\AppleClient\Service\Retry;

use Saloon\Http\Request;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Exceptions\Request\FatalRequestException;

interface RetryHandlerInterface
{
    /**
     * 处理重试逻辑
     */
    public function __invoke(FatalRequestException|RequestException $exception, Request $request): bool;
} 
