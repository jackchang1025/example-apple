<?php

namespace Modules\IpAddress\Service;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\IpAddress\Service\Requests\PconLineRequest;
use Modules\IpAddress\Service\Responses\IpResponse;
use Psr\Log\LoggerInterface;
use Saloon\Http\Request;

class IpService
{

    public function __construct(
        protected IpConnector $connector,
        protected \Illuminate\Http\Request $request,
        protected  LoggerInterface $logger
    )
    {
        $this->connector->withLogger($logger);
    }

    /**
     * @param Request $request
     * @return \Saloon\Http\Response
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function ipAddressByRequest(Request $request): \Saloon\Http\Response
    {
        return $this->connector->send($request);
    }

    public function ipAddressByPconLine(string $ip): \Saloon\Http\Response
    {
        return $this->ipAddressByRequest(new PconLineRequest($ip));
    }

    public function rememberIpAddress():?IpResponse
    {
        $ip = $this->request->ip();

        return Cache::remember("client_ip_address:{$ip}",999999,fn() => $this->getIpAddress($ip));
    }

    public function getIpAddress(string $ip): ?IpResponse
    {
        try {

            $response = $this->ipAddressByPconLine($ip);
            /**
             * @var IpResponse $dto
             */
            $dto = $response->dto();

            Log::info('获取IP地址成功',['ipaddress' => $dto->all()]);

            return $dto;
        } catch (\Exception $e) {

            return null;
        }
    }
}
