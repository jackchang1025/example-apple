<?php

namespace Modules\PhoneCode\Service;

use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;

trait Logger
{
    protected bool $booted = false;

    public function bootLogger(PendingRequest $pendingRequest): void
    {
        $pendingRequest->middleware()->onRequest(function (PendingRequest $pendingRequest) {

            $this->getLogger()
                ?->info('request', [
                    'method'  => $pendingRequest->getMethod(),
                    'uri'     => $pendingRequest->getUri(),
                    'headers' => $pendingRequest->headers(),
                    'body'    => $pendingRequest->body(),
                ]);

            return $pendingRequest;
        });

        $pendingRequest->middleware()->onResponse(function (Response $response) {
            $this->getLogger()
                ?->info('response', [
                    'status'  => $response->status(),
                    'headers' => $response->headers(),
                    'body'    => $response->body(),
                ]);

            return $response;
        });
    }

    public function handleRetry(FatalRequestException|RequestException $exception, Request $request): bool
    {
        $response = $exception->getResponse();

        $this->getLogger()
            ?->info('response', [
                'status'  => $response->status(),
                'headers' => $response->headers(),
                'body'    => $response->body(),
            ]);

        return true;
    }
}
