<?php

namespace Modules\IpProxyManager\Service;

use JsonException;
use Modules\IpAddress\Service\IpService;
use Modules\IpProxyManager\Service\Trait\HasProxy;
use Psr\Log\LoggerInterface;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class ProxyService
{
    use HasProxy;

    protected ?ProxyResponse $proxy = null;

    protected BaseDto $dto;

    /**
     * @param ProxyConnector $connector
     * @param LoggerInterface $logger
     * @param IpService $ipService
     * @param Request $request
     */
    public function __construct(
        protected  ProxyConnector $connector,
        protected  LoggerInterface $logger,
        protected IpService $ipService,
        protected  Request $request
    )
    {
        $this->dto = $this->request->getDto();
        $this->connector->withLogger($logger);

        $this->enableIpaddress($this->dto->get('ipaddress_enabled'));
        $this->enableProxy($this->dto->get('proxy_enabled'));
    }

    public function refreshProxy(array $option = []): ?ProxyResponse
    {
        return $this->proxy = $this->fetchProxy($option);
    }

    /**
     * Retrieves the proxy option based on IP address status and details.
     *
     * This function checks if the IP address feature is enabled through the proxy service.
     * If enabled, it retrieves the IP address details, ensuring it's a chain IP, and then returns
     * an array containing city and province codes for proxy configuration.
     *
     * @return array An associative array containing 'city' and 'province' keys with respective codes,
     *               or an empty array if the IP address feature is disabled or the IP is not a chain.
     */
    private function getProxyOption(): array
    {
        if (!$this->isIpaddressEnabled()) {
            return [];
        }

        $ipAddress = $this->ipService->rememberIpAddress();
        if (!$ipAddress || !$ipAddress->isChain()) {
            return [];
        }

        return [
            'city'     => $ipAddress->getCityCode(),
            'province' => $ipAddress->getProCode(),
        ];
    }

    protected function fetchProxy(array $option = []): ProxyResponse|null
    {
        try {

            $response = $this->send(array_merge($option, $this->getProxyOption()));

            /**
             * @throws JsonException
             * @var BaseDto $dot
             */
            $dot = $response->dto();

            $list = $dot->getProxyList();

            return $this->proxy ??= $list->first();

        } catch (FatalRequestException|RequestException|JsonException $e) {

            return null;
        }
    }

    /**
     * @param array $option
     * @return ProxyResponse|null
     */
    public function getProxy(array $option = []): ?ProxyResponse
    {
        return $this->proxy ??= $this->fetchProxy($option);
    }

    public function setProxy(?ProxyResponse $proxy): void
    {
        $this->proxy = $proxy;
    }

    /**
     * @param array $option
     * @return \Saloon\Http\Response
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function send(array $option = []): \Saloon\Http\Response
    {
        $this->dto->merge($option);

        return $this->connector->send($this->request);
    }
}
