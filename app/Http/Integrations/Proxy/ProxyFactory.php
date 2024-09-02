<?php

namespace App\Http\Integrations\Proxy;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Psr\Log\LoggerInterface;
use Saloon\Http\Request;

class ProxyFactory
{
    protected ProxyConnector $proxyConnector;

    protected Request $request;
    protected BaseDto $dto;

    public function __construct(protected Container $container,protected LoggerInterface $logger,protected ProxyConfiguration $config)
    {
    }

    /**
     * @throws BindingResolutionException|\Exception
     */
    public function create(?ProxyConfiguration $config = null): ProxyFactory
    {
        $this->config = $config ?? $this->config;

        /**
         * @var ProxyConnector $container
         */
        $this->proxyConnector = $this->container->make($this->config->getDefaultDriverClass());

        $request = $this->config->getDefaultModeClass();
        $config = $this->config->getDefaultDriverConfig();
        $this->dto = $this->createDto($request, $config);

        $this->request = $this->createRequest($request, $this->dto);

        return $this;
    }

    /**
     * @param array $data
     * @return \Saloon\Http\Response
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function send(array $data): \Saloon\Http\Response
    {
        $this->dto->merge($data);
        return $this->proxyConnector->send($this->request);
    }

    public function getProxyConnector(): ProxyConnector
    {
        return $this->proxyConnector;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getDto(): BaseDto
    {
        return $this->dto;
    }

    protected function createRequestContainer(?ProxyConfiguration $config = null):ProxyConnector
    {
        return $this->container->make($config->getDefaultDriverClass());
    }

    protected function createDto(string $requestClass, array $config):BaseDto
    {
        $reflection = new \ReflectionClass($requestClass);
        $constructor = $reflection->getConstructor();

        if ($constructor && $params = $constructor->getParameters()) {
            $dtoParam = $params[0];
            $dtoClass = $dtoParam->getType()->getName();

            return new $dtoClass($config);
        }

        throw new \Exception('No dto class found');
    }

    /**
     * @throws \Exception
     */
    protected function createRequest(string $requestClass, BaseDto $dto): Request
    {
        return new $requestClass($dto);
    }
}
