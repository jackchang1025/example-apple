<?php

namespace App\Apple\Service\Client;

use App\Apple\Proxy\Option;
use App\Apple\Proxy\ProxyInterface;
use App\Apple\Proxy\ProxyManager;
use App\Apple\Service\User\User;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJarInterface;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

abstract class BaseClient
{
    protected ?Client $client = null;

    protected ?string $proxyUrl = null;

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

    public function setProxyUrl(?string $proxyUrl): void
    {
        $this->proxyUrl = $proxyUrl;
    }

    public function getProxyUrl(): ?string
    {
        if ($this->proxyUrl) {
            return $this->proxyUrl;
        }
        $option = Option::make([
            'uid' => $this->user->getToken(),
        ]);
        $this->proxyUrl = $this->proxy->getProxy($option);
        if (empty($this->proxyUrl)){
            throw new RuntimeException('Proxy not found');
        }

        $this->logger->info("token: {$this->user->getToken()} proxy: $this->proxyUrl get proxy success");
        return $this->proxyUrl;
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
