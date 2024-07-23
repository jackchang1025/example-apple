<?php

namespace App\Apple\Service;

use App\Apple\Proxy\ProxyManager;
use App\Apple\Service\Client\AppleIdClient;
use App\Apple\Service\Client\ClientFactory;
use App\Apple\Service\Client\IdmsaClient;
use App\Apple\Service\Client\PhoneCodeClient;
use App\Apple\Service\Cookies\CookieManagerFactory;
use App\Apple\Service\User\UserFactory;
use GuzzleHttp\Cookie\CookieJarInterface;
use Psr\Log\LoggerInterface;

readonly class AppleFactory
{
    public function __construct(
        private UserFactory $userFactory,
        private CookieManagerFactory $cookieManagerFactory,
        private ClientFactory $clientFactory,
        private LoggerInterface $logger,
        protected ProxyManager $proxyManager,
    ) {}

    public function create(string $guid): Apple
    {
        $user = $this->userFactory->create($guid);
        $cookieJar = $this->cookieManagerFactory->create($guid);

        $proxy = $this->proxyManager->driver();

        $idmsaClient = new IdmsaClient(clientFactory: $this->clientFactory, cookieJar: $cookieJar, logger: $this->logger,user: $user,proxy: $proxy);
        $appleIdClient = new AppleIdClient(clientFactory: $this->clientFactory, cookieJar: $cookieJar, logger: $this->logger,user: $user,proxy: $proxy);
        $phoneCodeClient = new PhoneCodeClient(clientFactory: $this->clientFactory, cookieJar: $cookieJar, logger: $this->logger,user: $user,proxy: $proxy);

        return new Apple($idmsaClient, $appleIdClient, $phoneCodeClient, $user,$cookieJar);
    }
}
