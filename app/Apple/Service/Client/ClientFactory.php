<?php

namespace App\Apple\Service\Client;

use App\Apple\Service\User\User;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Str;
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
                if (!empty($value) && $request->hasHeader($name) === false && in_array($name, ['scnt'])){
                    $request = $request->withAddedHeader($name, $value);
                }
            }

            // 添加 Referer 头
            $request->withAddedHeader('Referer', $request->getUri()->getHost());

            // 格式化并打印 response headers
            $formattedHeaders = [];
            foreach ($request->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    $formattedHeaders[] = "$name: $value";
                }
            }
            $headersString = implode("\n", $formattedHeaders);

            $this->logger->debug("Request Headers:\n$headersString");
            $this->logger->debug("Request body:\n{$request->getBody()}");
            $this->logger->debug("proxy_url:{$this->user->get('proxy_url')}");
            $this->logger->debug("proxy_ip:{$this->user->get('proxy_ip')}");
            $this->logger->debug("account:{$this->user->get('account')}");

            $this->logger->info('Request', [
                'method'  => $request->getMethod(),
                'uri'     => (string)$request->getUri(),
                'headers' => $request->getHeaders(),
                'body'    => (string)$request->getBody(),
                'proxy_url'    => $this->user->get('proxy_url'),
                'proxy_ip'    => $this->user->get('proxy_ip'),
            ]);

            return $request;
        }));

        $stack->push(Middleware::mapResponse(function (ResponseInterface $response) {

            // 格式化并打印 response headers
            $formattedHeaders = [];
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    $formattedHeaders[] = "$name: $value";

                    if ($name === 'scnt'){
                        $this->user->appendHeader('scnt', $value);
                    }

                    if (str_contains($name, 'X-Apple')){//X-Apple-ID
                        $this->user->appendHeader($name, $value);
                    }
                }
            }
            $headersString = implode("\n", $formattedHeaders);

            $this->logger->debug("Response Headers:\n$headersString");
            $this->logger->debug("account:{$this->user->get('account')}");

            $body = (string) $response->getBody();
            if (Str::length($body) > 2000){
                $body = Str::substr($body, 0, 2000);
            }
            //验证 $body 的长度
            $this->logger->info('Response', [
                'status'  => $response->getStatusCode(),
                'reason'  => $response->getReasonPhrase(),
                'headers' => $response->getHeaders(),
                'body'    => $body,
            ]);

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
