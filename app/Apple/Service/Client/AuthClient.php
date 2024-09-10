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
use Psr\Log\LoggerInterface;

class AuthClient extends BaseClient
{

    protected string $url = '';
    public function __construct(
         ClientFactory $clientFactory,
         CookieJarInterface $cookieJar,
         LoggerInterface $logger,
         User $user,
         ProxyInterface $proxy,
         string $url,
         ?Config $config = null,
    ) {

        parent::__construct(
            clientFactory: $clientFactory,
            cookieJar: $cookieJar,
            logger: $logger,
            user: $user,
            proxy: $proxy,
            config: $config
        );

        $this->url = $url;
    }

    /**
     * @return PendingRequest
     */
    protected function createClient(): PendingRequest
    {
        return $this->clientFactory->create([
            'base_uri'              => $this->url,
            'timeout'               => 10,
            'connect_timeout'       => 30,
            'verify'                => false,

            RequestOptions::HEADERS => [
            ],
        ]);
    }

    /**
     * 获取授权页面
     * @param string $account
     * @return Response
     * @throws ConnectionException
     * @throws RequestException
     */
    public function init(string $account): Response
    {
        return $this->request('POST', '/init', [
            RequestOptions::JSON        => [
                'email' => $account,
            ],
        ]);
    }
    /**
     * @param string $key
     * @param string $salt
     * @param string $b
     * @param string $c
     * @param string $password
     * @param string $iteration
     * @param string $protocol
     * @return Response
     * @throws ConnectionException
     * @throws RequestException
     */
    public function complete(string $key,string $salt,string $b,string $c,string $password,string $iteration,string $protocol): Response
    {
        return $this->request('post', '/complete', [
            RequestOptions::JSON        => [
                'key' => $key,
                'value' =>[
                    'b'    => $b,
                    'c'    => $c,
                    'salt'  => $salt,
                    'password'  => $password,
                    'iteration'  => $iteration,
                    'protocol'  => $protocol,
                ],
            ],
        ]);
    }
}
