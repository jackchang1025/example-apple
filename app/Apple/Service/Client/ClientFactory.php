<?php

namespace App\Apple\Service\Client;

use App\Apple\Service\User\User;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class ClientFactory
{
    private const string USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    protected ?User $user = null;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ?ContainerInterface $container = null,
    )
    {
    }

    public function create(User $user,array $additionalConfig = []): Client
    {
        $this->user = $user;
        $stack = HandlerStack::create();


        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {

            $headers = $this->user->getHeaders();
            foreach ($headers as $name => $value) {
                if (!empty($value) && $request->hasHeader($name) === false && $name == 'scnt'){
                    $request = $request->withAddedHeader($name, $value);
                }
            }

            // 添加 Referer 头
            $request->withAddedHeader('Referer', $request->getUri()->getHost());

            $requestInfo = [
                'method'  => $request->getMethod(),
                'uri'     => $request->getUri()->getPath(),
                'headers' => $request->getHeaders(),
                'body'    => (string)$request->getBody(),
                'proxy_url'    => $this->user->get('proxy_url'),
                'proxy_ip'    => $this->user->get('proxy_ip'),
                'account'    => $this->user->get('account'),
            ];
            $this->logger->info('Request',$requestInfo);

            return $request;
        }));

        $stack->push(Middleware::mapResponse(function (ResponseInterface $response){

            // 格式化并打印 response headers
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {

                    if ($name === 'scnt'){
                        $this->user->appendHeader('scnt', $value);
                    }

                    if (str_contains($name, 'X-Apple')){
                        $this->user->appendHeader($name, $value);
                    }
                }
            }

            $contentType = $response->getHeader('Content-Type');
            if (!empty($contentType[0]) && $contentType[0] === 'text/html;charset=UTF-8'){
                return $response;
            }

            $responseInfo = [
                'account'  => $this->user->get('account'),
                'status'  => $response->getStatusCode(),
                'reason'  => $response->getReasonPhrase(),
                'headers' => $response->getHeaders(),
                'body'    => (string) $response->getBody(),
            ];

            $this->logger->info('Response', $responseInfo);

            // 重要：将响应体的指针重置到开始位置
            $response->getBody()->rewind();
            return $response;
        }));

        if (empty($additionalConfig['handler'])){
            $additionalConfig['handler'] = $stack;
        }

        if (empty($additionalConfig['User-Agent'])){
            $additionalConfig['User-Agent'] = self::USER_AGENT;
        }

        if ($this->container !== null && method_exists($this->container, 'make')) {
            // Create by DI for AOP.
            return $this->container->make(Client::class, ['config' => $additionalConfig]);
        }

        return new Client($additionalConfig);
    }
}
