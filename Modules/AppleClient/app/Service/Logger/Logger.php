<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Logger;

use Illuminate\Support\Str;
use Modules\AppleClient\Service\Trait\HasPipeline;
use Psr\Log\LoggerInterface;
use Saloon\Enums\PipeOrder;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

trait Logger
{
    use HasPipeline;

    protected ?LoggerInterface $logger = null;

    public function bootLogger(PendingRequest $pendingRequest): void
    {
        if(!$this->requestPipelineExists($pendingRequest,'logger_request')){

            $pendingRequest->getConnector()
                ->middleware()
                ->onRequest(
                function (PendingRequest $request) {

                    $this->getLogger()?->debug('request', [
                        'method' => $request->getMethod(),
                        'uri'    => (string)$request->getUri(),
                        'store'  => $request->getConnector()->getApple()->getCacheStore()->all(),
                        'config'  => $request->config()->all(),
                        'headers' => $request->headers()->all(),
                        'body'    => $request->body()?->all(),
                    ]);

                    return $request;
                },
                'logger_request',
                PipeOrder::LAST
            );

        }

        if(!$this->responsePipelineExists($pendingRequest,'logger_response')){

            $pendingRequest->getConnector()->middleware()->onResponse(
                function (Response $response){

                    $body = trim($response->body());

                    if (Str::length($body) > 2000) {
                        $body = Str::substr($body, 0, 2000);
                    }

                    $this->getLogger()?->debug('response', [
                        'status' => $response->status(),
                        'headers' => $response->headers()->all(),
                        'body' => $body,
                    ]);

                    return $response;
                },
                'logger_response',
                PipeOrder::FIRST
            );
        }
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function withLogger(?LoggerInterface $logger): static
    {
        $this->logger = $logger;

        return $this;
    }
}
