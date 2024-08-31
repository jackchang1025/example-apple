<?php

namespace App\Apple;

use App\Apple\Integrations\AppleId\AppleIdConnector;
use App\Apple\Integrations\Idmsa\IdmsaConnector;
use App\Apple\Integrations\Phone\PhoneConnector;
use App\Apple\Integrations\Response;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Saloon\Traits\Macroable;

class Apple
{
    use Macroable;
    use AppleId;
    use Idmsa;
    use Phone;

    protected AppleIdConnector $appleIdConnector;
    protected IdmsaConnector $idmsaConnector;
    protected PhoneConnector $phoneConnector;

    public function __construct(protected CacheInterface $cache,protected LoggerInterface $logger,protected string $clientId)
    {
        $this->appleIdConnector = new AppleIdConnector($cache,$this->logger,$clientId);
        $this->idmsaConnector = new IdmsaConnector($cache,$this->logger,$clientId);
        $this->phoneConnector = new PhoneConnector();
    }

    public function login(string $accountName, string $password, bool $rememberMe = true): Response
    {
        $this->authorizeSing($accountName,$password,$rememberMe);

        return $this->auth();
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getAppleIdConnector(): AppleIdConnector
    {
        return $this->appleIdConnector;
    }

    public function getIdmsaConnector(): IdmsaConnector
    {
        return $this->idmsaConnector;
    }

    function getPhoneConnector(): PhoneConnector
    {
        return $this->phoneConnector;
    }
}
