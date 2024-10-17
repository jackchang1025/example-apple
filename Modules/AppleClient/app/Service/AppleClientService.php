<?php

namespace Modules\AppleClient\Service;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\AppleClient\Service\Trait\HasAuth;
use Modules\AppleClient\Service\Trait\HasBindPhone;
use Modules\AppleClient\Service\Trait\HasDevices;
use Modules\AppleClient\Service\Trait\HasNotification;
use Modules\AppleClient\Service\Trait\HasPayment;
use Modules\AppleClient\Service\Trait\HasPhone;
use Modules\AppleClient\Service\Trait\HasRetry;
use Modules\AppleClient\Service\Trait\HasSign;
use Modules\AppleClient\Service\Trait\HasStore;
use Modules\IpAddress\Service\IpService;
use Modules\IpProxyManager\Service\ProxyService;
use Modules\PhoneCode\Service\PhoneConnector;
use Psr\Log\LoggerInterface;

/**
 * @mixin AppleClient
 */
class AppleClientService
{
    use HasSign;
    use HasStore;
    use HasAuth;
    use HasPhone;
    use HasRetry;
    use HasBindPhone;
    use HasNotification;
    use HasPayment;
    use HasDevices;

    private const int|float LOGIN_CACHE_TTL = 60 * 5;

    private const int RETRY_INTERVAL = 1; // 登录状态缓存时长（5分钟）

    private const int DEFAULT_RETRY_ATTEMPTS = 5;


    public function __construct(
        protected AppleClient $client,
        protected ProxyService $proxyService,
        protected IpService $ipService,
        protected PhoneConnector $phoneConnector,
        protected LoggerInterface $logger,
    ) {

    }

    /**
     * Retrieves the phone connector instance.
     *
     * This method returns the currently set phone connector object which can be used for further communication operations.
     *
     * @return PhoneConnector The phone connector instance configured for the application.
     */
    public function getPhoneConnector(): PhoneConnector
    {
        return $this->phoneConnector;
    }

    /**
     * Checks if the user is logged in based on the cache.
     *
     * Utilizes the cache system to determine if a login session exists for the user
     * identified by their unique GUID. Returns true if a login cache entry is present, false otherwise.
     *
     * @return bool True if the user is logged in, false otherwise.
     */
    public function hasLogin(): bool
    {
        return Cache::has("is_login:{$this->client->getSessionId()}");
    }

    /**
     * Sets the login status for the current user in the cache.
     *
     * This function stores the current timestamp associated with the user's GUID
     * in the cache, indicating that the user is logged in. The entry expires after
     * a predefined time defined by the `LOGIN_CACHE_TTL` constant.
     *
     * @return bool True if the login status was successfully set in the cache, false otherwise.
     */
    public function establishLoginSession(): bool
    {
        return Cache::put("is_login:{$this->client->getSessionId()}", Carbon::now(), self::LOGIN_CACHE_TTL);
    }

    /**
     * Retrieves the instance of the AppleClient.
     *
     * Returns the configured AppleClient instance which can be used for further interactions
     * with Apple services.
     *
     * @return AppleClient The configured instance of the AppleClient.
     */
    public function getClient(): AppleClient
    {
        return $this->client;
    }

    /**
     * Retrieves the proxy service instance.
     *
     * Returns the instance of the proxy service which is used for fetching proxy details.
     *
     * @return ProxyService The proxy service instance.
     */
    public function getProxyService(): ProxyService
    {
        return $this->proxyService;
    }

    /**
     * Retrieves the IP service instance.
     *
     * Returns the currently set IP service which can be utilized for operations related to IP addressing.
     *
     * @return IpService The instance of the IP service.
     */
    public function getIpService(): IpService
    {
        return $this->ipService;
    }

    /**
     * Handles dynamic method calls to the apple client service.
     *
     * This magic method allows the invocation of methods on the apple client service instance
     * by dynamically passing the method name and its parameters. It acts as a passthrough,
     * forwarding the call to the underlying apple client service with the provided arguments.
     *
     * @param string $name The name of the method to be called dynamically.
     * @param array $parameters An array of parameters to be passed to the method call.
     *
     * @return mixed The result of the method call on the apple client service, if any.
     */
    public function __call(string $name, array $parameters)
    {
        return $this->client->$name(...$parameters);
    }

}
