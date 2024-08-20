<?php

namespace App\Apple\Service\Client;

use App\Apple\Proxy\Option;
use App\Apple\Proxy\ProxyInterface;
use App\Apple\Proxy\ProxyResponse;
use App\Apple\Service\User\User;
use Exception;
use GuzzleHttp\Cookie\CookieJarInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
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

        $option = Option::make([
            'session' => $this->user->getToken(),
        ]);

        $this->proxyResponse = $this->proxy->getProxy($option);
        $this->user->set('proxy_url', $this->proxyResponse->getUrl());
        $this->user->set('proxy_ip', $this->proxy->getProxyIp($this->proxyResponse));
        $this->logger->info(sprintf('token %s Proxy: %s get proxy success',$this->user->getToken(), json_encode($this->proxyResponse->all(), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)));
        return $this->proxyResponse;
    }


    public function getClient(): PendingRequest
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
     * @throws ConnectionException
     */
    protected function request(string $method, string $uri, array $options = []): Response
    {
        $response = $this->getClient()
            ->retry(3,1000,function  (Exception $exception, PendingRequest $request){

                if ($exception instanceof ConnectionException){
                    $this->client = null;
                    $this->proxyResponse = null;
                    return true;
                }
                return false;
            },false)
            ->send($method, $uri, $options);

        return new Response(
            response: $response,
        );
    }
}
