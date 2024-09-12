<?php

namespace App\Apple\Service\Client;

use App\Apple\Proxy\ProxyInterface;
use App\Apple\Service\User\Config;
use App\Apple\Service\User\User;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseClient
{
    use HasProxy;
    use HasOption;
    use HasRetry;

    const string BASEURL_IDMSA = 'https://idmsa.apple.com';
    const string BASEURL_APPLEID = 'https://appleid.apple.com';

    protected ?PendingRequest $client = null;

    public function __construct(
        protected ClientFactory $clientFactory,
        protected CookieJarInterface $cookieJar,
        protected LoggerInterface $logger,
        protected User $user,
        protected ProxyInterface $proxy,
        protected ?Config $config = null,
    ) {
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return Response
     * @throws ConnectionException|RequestException
     */
    protected function request(string $method, string $uri, array $options = []): Response
    {
        $response = $this->getClient()
            ->async(false)
            ->retry($this->tries, $this->retryDelay, $this->handleRetry(), $this->retryThrow)
            ->send($method, $uri, $options)
            ->throwIfStatus(401);

        return new Response(
            response: $response,
        );
    }

    public function getClient(): PendingRequest
    {
        if ($this->client === null) {
            $this->client = $this->createClient();

            $this->client->withRequestMiddleware(function (RequestInterface $request) {

                $headers = $this->user->getHeaders();
                foreach ($headers as $name => $value) {
                    if (!empty($value) && !$request->hasHeader($name) && $name === 'scnt') {
                        $request = $request->withHeader($name, $value);
                    }
                }

                $request->withHeader('Referer', (string)$request->getUri());

                $this->logger->debug('Request', [
                    'account' => $this->user->get('accountName'),
                    'proxy'   => $this->getProxyResponse()?->all(),
                    'method'  => $request->getMethod(),
                    'uri'     => (string)$request->getUri(),
                    'headers' => $request->getHeaders(),
                    'body'    => (string)$request->getBody(),
                ]);

                return $request;
            });

            $this->client->withResponseMiddleware(function (ResponseInterface $response) {

                foreach ($response->getHeaders() as $name => $values) {
                    foreach ($values as $value) {
                        if ($name === 'scnt') {
                            $this->user->appendHeader('scnt', $value);
                        }
                        if (str_contains($name, 'X-Apple')) {
                            $this->user->appendHeader($name, $value);
                        }
                    }
                }

                $contentType = $response->getHeaderLine('Content-Type');
                if ($contentType !== 'text/html;charset=UTF-8') {

                    $body = (string)$response->getBody();
                    if (Str::length($body) > 2000) {
                        $body = Str::substr($body, 0, 2000);
                    }

                    $responseInfo = [
                        'account' => $this->user->get('accountName'),
                        'status'  => $response->getStatusCode(),
                        'headers' => $response->getHeaders(),
                        'body'    => $body,
                    ];
                    $this->logger->debug('Response', $responseInfo);
                }

                return $response;
            });
        }

        return $this->client;
    }

    protected function createClient(): PendingRequest
    {
        return $this->clientFactory->create($this->buildClientOptions());
    }

    protected function buildClientOptions(): array
    {
        $options = $this->defaultOption();

        if (empty($options[RequestOptions::PROXY]) && $this->isProxyEnabled()) {
            $cachedProxyResponse = $this->getOrCreateProxyResponse();
            if ($cachedProxyResponse) {
                $options[RequestOptions::PROXY] = $cachedProxyResponse->getUrl();
            }
        }

        return $options;
    }

    abstract protected function defaultOption(): array;

    protected function getConfig(): Config
    {
        return $this->config;
    }
}
