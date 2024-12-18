<?php

namespace Modules\AppleClient\Service\Trait;

use Psr\Log\LoggerInterface;
use Saloon\Enums\PipeOrder;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

trait HasLogger
{
    use HasPipelineExists;

    protected ?LoggerInterface $logger = null;

    public function withLogger(?LoggerInterface $logger = null): static
    {
        $this->logger = $logger;

        return $this;
    }

    public function bootHasLogger(PendingRequest $pendingRequest): void
    {
        $this->registerRequestLoggerMiddleware($pendingRequest);
        $this->registerResponseLoggerMiddleware($pendingRequest);
    }

    protected function registerRequestLoggerMiddleware(PendingRequest $pendingRequest): void
    {
        if (!$this->requestPipelineExists($pendingRequest, 'logger_request')) {
            $pendingRequest->getConnector()
                ->middleware()
                ->onRequest(
                    fn(PendingRequest $request) => $this->formatRequestLog($request),
                    'logger_request',
                    PipeOrder::LAST
                );
        }
    }

    protected function formatRequestLog(PendingRequest $request): ?PendingRequest
    {
        $this->getLogger()?->debug('request', [
            'method'  => $request->getMethod(),
            'uri'     => (string)$request->getUri(),
            'headers' => $request->headers(),
            'body'    => (string)$request->body(),
        ]);

        return $request;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    protected function registerResponseLoggerMiddleware(PendingRequest $pendingRequest): void
    {
        if (!$this->responsePipelineExists($pendingRequest, 'logger_response')) {

            $pendingRequest->getConnector()
                ->middleware()
                ->onResponse(
                    fn(Response $response) => $this->formatResponseLog($response),
                    'logger_response',
                    PipeOrder::FIRST
                );
        }
    }

    protected function formatResponseLog(Response $response): ?Response
    {
        $this->getLogger()?->debug('response', [
            'status'  => $response->status(),
            'headers' => $response->headers(),
            'body'    => $response->body(),
        ]);

        return $response;
    }
}
