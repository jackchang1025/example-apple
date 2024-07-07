<?php

namespace App\Apple\Service;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class ClientFactory
{
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';


    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ?ContainerInterface $container = null,
    )
    {
    }

    public function create(Config $config, array $additionalConfig = []): Client
    {
        $stack = HandlerStack::create();
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {

            $this->logger->info('Request', [
                'method'  => $request->getMethod(),
                'uri'     => (string)$request->getUri(),
                'headers' => $request->getHeaders(),
                'body'    => (string)$request->getBody(),
            ]);

            return $request;
        }));

        $stack->push(Middleware::mapResponse(function (ResponseInterface $response) {
            $this->logger->info('Response', [
                'status'  => $response->getStatusCode(),
                'reason'  => $response->getReasonPhrase(),
                'headers' => $response->getHeaders(),
                'body'    => (string)$response->getBody(),
            ]);
            // 重要：将响应体的指针重置到开始位置
            $response->getBody()->rewind();
            return $response;
        }));


        $defaultConfig = [
            'base_uri'              => $config->getServiceUrl(),//https://idmsa.apple.com/appleauth
            'timeout'               => $config->getTimeOutInterval(),
            'connect_timeout'       => $config->getModuleTimeOutInSeconds(),
            'verify'                => false,
            RequestOptions::HEADERS => [
                'X-Apple-Widget-Key'          => $config->getServiceKey(),
                'X-Apple-OAuth-Redirect-URI'  => $config->getApiUrl(),//'https://appleid.apple.com',
                'X-Apple-OAuth-Client-Id'     => $config->getServiceKey(),
                'X-Apple-OAuth-Client-Type'   => 'firstPartyAuth',
                'x-requested-with'            => 'XMLHttpRequest',
                'X-Apple-OAuth-Response-Mode' => 'web_message',
                'X-APPLE-HC'                  => '1:12:20240626165907:82794b5d498b7d7dc29740b23971ded5::4824',
                'X-Apple-Domain-Id'           => '1',
                'Origin'                      => $config->getServiceUrl(),
                'Referer'                     => $config->getServiceUrl(),
                'Accept'                      => 'application/json, text/javascript, */*; q=0.01',
                'User-Agent'                  => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36',
                'Content-Type'                => 'application/json',
                'Priority'                    => 'u=1, i',
                'Sec-Ch-Ua'                   => "Chromium;v=124, Google Chrome;v=124",
                'Sec-Ch-Ua-Mobile'            => '?0',
                'Sec-Ch-Ua-Platform'          => 'Windows',
                'Sec-Fetch-Dest'              => 'empty',
                'Sec-Fetch-Mode'              => 'cors',
                'Sec-Fetch-Site'              => 'same-origin',
            ],
            'User-Agent'            => self::USER_AGENT,
            'handler'               => $stack,
        ];


        $config = array_merge($defaultConfig, $additionalConfig);


        if ($this->container !== null && method_exists($this->container, 'make')) {
            // Create by DI for AOP.
            return $this->container->make(Client::class, ['config' => $config]);
        }

        return new Client($config);
    }
}
