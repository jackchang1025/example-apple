<?php

namespace App\Apple\Service\Client;

use App\Apple\Proxy\Option;
use App\Apple\Proxy\ProxyInterface;
use App\Apple\Proxy\ProxyResponse;
use App\Apple\Service\User\User;
use Exception;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
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

        $this->proxyResponse = $this->getProxy();
        $this->user->set('proxy_url', $this->proxyResponse->getUrl());
        $this->user->set('proxy_ip', $this->proxy->getProxyIp($this->proxyResponse));
        $this->logger->info(sprintf('token %s Proxy: %s get proxy success',$this->user->getToken(), json_encode($this->proxyResponse->all(), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)));
        return $this->proxyResponse;
    }

    public function getProxy(?array $options = null): ProxyResponse
    {
        $options ??= [
            'session' => $this->user->getToken(),
        ];
       return $this->proxy->getProxy(Option::make($options));
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
            ->retry(5,fn(int $attempt, Exception $exception) => $attempt * 100,function  (Exception $exception, PendingRequest $request){

                return $this->hasSwitchProxy($exception) ? $request->withOptions([
                    'proxy' => $this->getProxy([])->getUrl(),
                ]) : false;

            },false)
            ->send($method, $uri, $options);

        return new Response(
            response: $response,
        );
    }

    protected function hasSwitchProxy(Exception $exception):bool
    {
        if ($exception instanceof ConnectionException){
            return true;
        }

        if ($exception instanceof RequestException){
            $handlerContext = $exception->getHandlerContext();
            if(!empty($handlerContext['errno']) && $handlerContext['errno'] === 56){
                return true;
            }
        }
        return false;
    }
}
