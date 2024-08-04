<?php

namespace App\Apple\Service;

use Illuminate\Http\Client\Factory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class HttpFactory
{

    public function __construct(protected Factory $factory,protected LoggerInterface $logger)
    {
    }

    public function create(): Factory
    {
        return $this->factory->globalRequestMiddleware(
            function (RequestInterface $request) {

                $this->logger->debug('Request', [
                    'method'  => $request->getMethod(),
                    'uri'     => (string)$request->getUri(),
                    'headers' => $request->getHeaders(),
                    'body'    => (string)$request->getBody(),
                ]);

                return $request;

            }
        )->globalResponseMiddleware(function (ResponseInterface $response) {

            $this->logger->debug('Response', [
                'status'  => $response->getStatusCode(),
                'reason'  => $response->getReasonPhrase(),
                'headers' => $response->getHeaders(),
                'body'    => (string)$response->getBody(),
            ]);
            // 重要：将响应体的指针重置到开始位置
            $response->getBody()->rewind();

            return $response;
        });
    }
}
