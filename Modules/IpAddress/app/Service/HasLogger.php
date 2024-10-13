<?php

namespace Modules\IpAddress\Service;

use Psr\Log\LoggerInterface;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

trait HasLogger
{
    protected ?LoggerInterface $logger = null;

    public function withLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;
        return $this;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    protected function defaultRequestMiddle(): \Closure
    {
        return function (PendingRequest $request){
            $this->getLogger()?->debug('request', [
                'method'  => $request->getMethod(),
                'uri'     => (string) $request->getUri(),
                'headers' => $request->headers(),
                'body'    => (string)$request->body(),
            ]);
            return $request;
        };
    }

    protected function defaultResponseMiddle(): \Closure
    {
        return function (Response $response){
            $this->getLogger()?->debug('response', [
                'status'  => $response->status(),
                'headers' => $response->headers(),
                'body'    => $response->body(),
            ]);
            return $response;
        };
    }

    public function bootHasLogger(PendingRequest $pendingRequest): void
    {
        $pendingRequest->getConnector()->middleware()->onRequest($this->defaultRequestMiddle());
        $pendingRequest->getConnector()->middleware()->onResponse($this->defaultResponseMiddle());
    }
}
