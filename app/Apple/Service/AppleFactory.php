<?php

namespace App\Apple\Service;

use App\Apple\Service\Client\AppleIdClient;
use App\Apple\Service\Client\IdmsaClient;
use App\Apple\Service\Client\PhoneCodeClient;
use App\Apple\Service\Cookies\CookieManagerFactory;
use App\Apple\Service\User\User;
use App\Apple\Service\User\UserFactory;
use Illuminate\Container\Container;

readonly class AppleFactory
{
    public function __construct(
        protected Container $container,
        private UserFactory $userFactory,
        private CookieManagerFactory $cookieManagerFactory,
    ) {}

    public function create(string $guid): Apple
    {
        $user = $this->userFactory->create($guid);
        $cookieJar = $this->cookieManagerFactory->create($guid);

        $idmsaClient = $this->container->make(IdmsaClient::class,[ 'cookieJar' => $cookieJar, 'user' => $user]);
        $appleIdClient = $this->container->make(AppleIdClient::class,[ 'cookieJar' => $cookieJar, 'user' => $user]);
        $phoneCodeClient = $this->container->make(PhoneCodeClient::class,[ 'cookieJar' => $cookieJar, 'user' => $user]);

        $apple =  $this->container->make(Apple::class,['idmsaClient' => $idmsaClient, 'appleIdClient' => $appleIdClient, 'phoneCodeClient' => $phoneCodeClient, 'user' => $user,'cookieJar' => $cookieJar]);

        $this->container->singleton(User::class,fn() => $user);
        $this->container->singleton(Apple::class,fn() => $apple);

        return $apple;
    }
}
