<?php

namespace App\Apple\Service\Client\Middleware;

use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

readonly class LogsMiddleware implements GlobalMiddlewareInterface
{

    public function __construct(
        protected LoggerInterface $logger,
    ) {
    }
    public function request(RequestInterface $request): RequestInterface
    {
        $this->logger->debug('Request', [
            'method'  => $request->getMethod(),
            'uri'     => (string) $request->getUri(),
            'headers' => $request->getHeaders(),
            'body'    => (string)$request->getBody(),
        ]);

        return $request;
    }

    public function response(ResponseInterface $response): ResponseInterface
    {
        $contentType = $response->getHeaderLine('Content-Type');
        if ($contentType !== 'text/html;charset=UTF-8') {

            $body = (string) $response->getBody();
            if (Str::length($body) > 2000){
                $body = Str::substr($body, 0, 2000);
            }

            $responseInfo = [
                'status'  => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body'    => $body,
            ];
            $this->logger->debug('Response', $responseInfo);
        }

        return $response;
    }
}
