<?php

namespace App\Proxy;

use Psr\Log\LoggerInterface;
use Weijiajia\IpProxyManager\BaseDto;
use Weijiajia\IpProxyManager\ProxyConnector;
use Weijiajia\IpProxyManager\ProxyResponse;
use Weijiajia\IpProxyManager\Request;

class ProxyService
{
    use HasProxy;

    protected BaseDto $dto;

    /**
     * @param ProxyConnector $connector
     * @param LoggerInterface $logger
     * @param Request $request
     */
    public function __construct(
        protected  ProxyConnector $connector,
        protected  LoggerInterface $logger,
        protected  Request $request
    )
    {
        $this->dto = $this->request->getDto();
        $this->connector->withLogger($logger);

        $this->enableIpaddress($this->dto->get('ipaddress_enabled'));
        $this->enableProxy($this->dto->get('proxy_enabled'));
    }

    public function refreshProxy(array $option = [])
    {
        $response = $this->send($option);

        /**
         * @var BaseDto $dot
         */
        $dot = $response->dto();

        $list = $dot->getProxyList();

        return $this->proxy ??= $list->first();
    }

    public function getProxy(array $option = []): ?ProxyResponse
    {
        if ($this->proxy){
            return $this->proxy;
        }

        $response = $this->send($option);

        /**
         * @var BaseDto $dot
         */
        $dot = $response->dto();

        $list = $dot->getProxyList();

        return $this->proxy ??= $list->first();
    }

    public function setProxy(?ProxyResponse $proxy): void
    {
        $this->proxy = $proxy;
    }

    public function send(array $option = []): \Saloon\Http\Response
    {
        $this->dto->merge($option);

        return $this->connector->send($this->request);
    }
}
