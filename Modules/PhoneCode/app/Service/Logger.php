<?php

namespace Modules\PhoneCode\Service;

use Psr\Log\LoggerInterface;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

trait Logger
{

    protected bool $booted = false;

    abstract public function getLogger(): ?LoggerInterface;

    public function defaultRequestMiddle(PendingRequest $pendingRequest): PendingRequest
    {
        $this->getLogger()
            ?->debug('request', [
                'method'  => $pendingRequest->getMethod(),
                'uri'     => $pendingRequest->getUri(),
                'headers' => $pendingRequest->headers(),
                'body'    => $pendingRequest->body(),
            ]);
        return $pendingRequest;
    }

    public function defaultResponseMiddle(Response $response): Response
    {
        $this->getLogger()
            ?->info('response', [
                'status'  => $response->status(),
                'headers' => $response->headers(),
                'body'    => $response->body(),
            ]);
        return $response;
    }

    public function bootLogger(PendingRequest $pendingRequest): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        $pendingRequest->middleware()->onRequest(function (PendingRequest $pendingRequest){

            return $this->defaultRequestMiddle($pendingRequest);
        });

        $pendingRequest->middleware()->onResponse(function (Response $response){
            return $this->defaultResponseMiddle($response);
        });

    }
}
