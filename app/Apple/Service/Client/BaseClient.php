<?php

namespace App\Apple\Service\Client;

use App\Apple\Proxy\Option;
use App\Apple\Proxy\ProxyInterface;
use App\Apple\Proxy\ProxyResponse;
use App\Apple\Service\User\Config;
use App\Apple\Service\User\User;
use App\Http\Integrations\IpConnector\Responses\IpResponse;
use Exception;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseClient
{
    protected ?PendingRequest $client = null;

    protected ?ProxyResponse $proxyResponse = null;

    const string BASEURL_IDMSA = 'https://idmsa.apple.com';

    const string BASEURL_APPLEID = 'https://appleid.apple.com';


    public function __construct(
        protected ClientFactory $clientFactory,
        protected CookieJarInterface $cookieJar,
        protected LoggerInterface $logger,
        protected User $user,
        protected ProxyInterface $proxy,
        protected ?Config $config = null,
    ) {
    }

    abstract protected function createClient(): PendingRequest;

    public function setProxyResponse(?ProxyResponse $proxyUrl): void
    {
        $this->proxyResponse = $proxyUrl;
    }

    public function getProxyResponse(): ?ProxyResponse
    {
        if ($this->proxyResponse) {
            return $this->proxyResponse;
        }

        $this->proxyResponse = $this->getProxy();
        $this->user->add('proxy_url', $this->proxyResponse->getUrl());
        $this->user->add('proxy_ip', $this->proxy->getProxyIp($this->proxyResponse));
        $this->logger->info(
            sprintf(
                'token %s Proxy: %s get proxy success',
                $this->user->getToken(),
                json_encode($this->proxyResponse->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            )
        );

        return $this->proxyResponse;
    }

    public function getProxy(?array $options = null): ProxyResponse
    {

        /**
         * @var IpResponse $ipaddress
         */
        $ipaddress = $this->user->get('ipaddress');

        if ($ipaddress !== null && $ipaddress->isChain()){
            $options['city'] = $ipaddress->cityCode();
            $options['province'] = $ipaddress->proCode();
        }

        return $this->proxy->getProxy(Option::make($options));
    }


    public function getClient(): PendingRequest
    {
        if ($this->client === null) {
            $this->client = $this->createClient();

            $this->client->withRequestMiddleware(function (RequestInterface $request) {

                Log::debug('HeaderMiddleware User', ['user' => $this->user->all()]);

                $headers = $this->user->getHeaders();
                foreach ($headers as $name => $value) {
                    if (!empty($value) && !$request->hasHeader($name) && $name === 'scnt') {
                        $request = $request->withHeader($name, $value);
                    }
                }

                $request->withHeader('Referer', (string) $request->getUri());

                $this->logger->debug('Request', [
                    'account'    => $this->user->get('accountName'),
                    'method'  => $request->getMethod(),
                    'uri'     => (string) $request->getUri(),
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

                    $body = (string) $response->getBody();
                    if (Str::length($body) > 2000){
                        $body = Str::substr($body, 0, 2000);
                    }

                    $responseInfo = [
                        'account'    => $this->user->get('accountName'),
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

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return Response
     * @throws ConnectionException|\Illuminate\Http\Client\RequestException
     */
    protected function request(string $method, string $uri, array $options = []): Response
    {
        $response = $this->getClient()
            ->async(false)
            ->retry(
                5,
                fn(int $attempt, Exception $exception) => $attempt * 100,
                function (Exception $exception, PendingRequest $request) {

                    return $this->hasSwitchProxy($exception) ? $request->withOptions([
                        'proxy' => $this->getProxy([])->getUrl(),
                    ]) : false;

                },
                false
            )
            ->send($method, $uri, $options)
            ->throwIfStatus(401);

        return new Response(
            response: $response,
        );
    }

    protected function hasSwitchProxy(Exception $exception): bool
    {
        return ($exception instanceof ConnectionException) || ($exception instanceof RequestException && !empty(
                $exception->getHandlerContext()
                ));
    }

    protected function getConfig(): Config
    {
        return $this->config;
    }
}
