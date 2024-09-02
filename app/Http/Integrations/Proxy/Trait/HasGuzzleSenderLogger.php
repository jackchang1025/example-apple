<?php

namespace App\Http\Integrations\Proxy\Trait;

use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Saloon\Http\PendingRequest;
use Saloon\Http\Senders\GuzzleSender;

trait HasGuzzleSenderLogger
{
    protected bool $booted = false;

    abstract public function getLogger(): LoggerInterface;

    public function defaultRequestMiddle(): \Closure
    {
        return function (RequestInterface $request){
            $this->getLogger()
                ->debug('request', [
                'method'  => $request->getMethod(),
                'uri'     => (string) $request->getUri(),
                'headers' => $request->getHeaders(),
                'body'    => (string)$request->getBody(),
            ]);
            return $request;
        };
    }

    public function defaultResponseMiddle(): \Closure
    {
        return function (ResponseInterface $response){
            $this->getLogger()
                ->info('response', [
                'status'  => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body'    => (string) $response->getBody(),
            ]);
            return $response;
        };
    }

    public function bootHasGuzzleSenderLogger(PendingRequest $pendingRequest): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        $connector = $pendingRequest->getConnector();

        $sender = $connector->sender();
        if ($sender instanceof GuzzleSender){

            $sender->getHandlerStack()
                ->push(Middleware::mapRequest($this->defaultRequestMiddle()));

            $sender->getHandlerStack()
                ->push(Middleware::mapResponse($this->defaultResponseMiddle()));
        }
    }
}
