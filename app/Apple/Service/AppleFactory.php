<?php

namespace App\Apple\Service;

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
        private LoggerInterface $logger
    ) {}

    public function create(string $guid): Apple
    {
        $user = $this->userFactory->create($guid);
        $cookieJar = $this->cookieManagerFactory->create($guid);

        $idmsaClient = new IdmsaClient($this->clientFactory, $cookieJar, $this->logger,$user);
        $appleIdClient = new AppleIdClient($this->clientFactory, $cookieJar,$this->logger,$user);
        $phoneCodeClient = new PhoneCodeClient($this->clientFactory, $cookieJar, $this->logger,$user);

        return new Apple($idmsaClient, $appleIdClient, $phoneCodeClient, $user,$cookieJar);
    }
}
