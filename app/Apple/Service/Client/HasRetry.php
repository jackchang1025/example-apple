<?php

namespace App\Apple\Service\Client;

use Closure;
use Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;

trait HasRetry
{

    protected array|int $tries = 3;

    protected Closure|int $retryDelay = 100;

    protected ?Closure $retryWhenCallback = null;

    protected bool $retryThrow = false;

    public function retry(int $times, Closure|int $delay = 100, ?Closure $when = null): self
    {
        $this->tries = $times;
        $this->retryDelay = $delay;
        $this->retryWhenCallback = $when;

        return $this;
    }

    public function handleRetry(): callable
    {
        return $this->retryWhenCallback ?? function (Exception $exception, PendingRequest $request) {

            if($this->isConnectionException($exception)){

                if($this->isProxyEnabled()){
                    return $request->withOptions([
                        RequestOptions::PROXY => $this->refreshProxyResponse()->getUrl(),
                    ]);
                }

                return $request;
            }

            return false;
        };
    }

    protected function isConnectionException(Exception $exception): bool
    {
        return ($exception instanceof ConnectionException) || ($exception instanceof RequestException && !empty(
                $exception->getHandlerContext()
                ));
    }

}
