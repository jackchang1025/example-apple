<?php

namespace App\Apple\Service\Client;

use App\Apple\Proxy\Option;
use App\Apple\Proxy\ProxyInterface;
use App\Apple\Proxy\ProxyResponse;
use App\Apple\Service\User\User;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJarInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseClient
{
    protected ?Client $client = null;

    protected ?ProxyResponse $proxyResponse = null;

    const string BASEURL_IDMSA = 'https://idmsa.apple.com';

    const string BASEURL_APPLEID = 'https://appleid.apple.com';


    public function __construct(
        protected ClientFactory $clientFactory,
        protected CookieJarInterface $cookieJar,
        protected LoggerInterface $logger,
        protected User $user,
        protected ProxyInterface $proxy,
    ) {
    }

    abstract protected function createClient(): Client;

    public function setProxyResponse(?ProxyResponse $proxyUrl): void
    {
        $this->proxyResponse = $proxyUrl;
    }

    public function getProxyResponse(): ?ProxyResponse
    {
        if ($this->proxyResponse) {
            return $this->proxyResponse;
        }

        $option = Option::make([
            'session' => $this->user->getToken(),
        ]);

        $this->proxyResponse = $this->proxy->getProxy($option);
        $this->user->set('proxy_url', $this->proxyResponse->getUrl());
        $this->user->set('proxy_ip', $this->proxy->getProxyIp($this->proxyResponse));
        $this->logger->info(sprintf('token %s Proxy: %s get proxy success',$this->user->getToken(), json_encode($this->proxyResponse->all(), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)));
        return $this->proxyResponse;
    }


    public function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = $this->createClient();
        }
        return $this->client;
    }
    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function request(string $method, string $uri, array $options = []): Response
    {
        $response = $this->getClient()->request($method, $uri, $options);
        return $this->parseJsonResponse($response);
    }

    /**
     * @param ResponseInterface $response
     * @return Response
     */
    protected function parseJsonResponse(ResponseInterface $response): Response
    {
        $body = (string) $response->getBody();

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('JSON decode error: ', ['message' => json_last_error_msg(), 'body' => $body]);
            $data = [];
        }

        if (!is_array($data)) {
            $data = [$data];
        }

        return new Response(
            response: $response,
            status: $response->getStatusCode(),
            data: $data
        );
    }

    private function RuntimeException(string $string)
    {
    }
}
