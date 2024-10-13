<?php

namespace Modules\AppleClient\Service;

use Closure;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\AppleClient\Service\Trait\HasAuth;
use Modules\AppleClient\Service\Trait\HasBindPhone;
use Modules\AppleClient\Service\Trait\HasNotification;
use Modules\AppleClient\Service\Trait\HasPhone;
use Modules\AppleClient\Service\Trait\HasRetry;
use Modules\AppleClient\Service\Trait\HasSign;
use Modules\AppleClient\Service\Trait\HasStore;
use Modules\IpAddress\Service\IpService;
use Modules\IpProxyManager\Service\ProxyService;
use Modules\PhoneCode\Service\PhoneConnector;
use Psr\Log\LoggerInterface;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Exceptions\SaloonException;
use Saloon\Http\Request;

/**
 * @mixin AppleClient
 */
final class AppleClientService
{
    use HasSign;
    use HasStore;
    use HasAuth;
    use HasPhone;
    use HasRetry;
    use HasBindPhone;
    use HasNotification;

    private const int|float LOGIN_CACHE_TTL = 60 * 5;

    private const int RETRY_INTERVAL = 1; // 登录状态缓存时长（5分钟）

    private const int DEFAULT_RETRY_ATTEMPTS = 5;

    protected ?string $guid;

    public function __construct(
        protected AppleClient $appleClient,
        protected ProxyService $proxyService,
        protected IpService $ipService,
        protected PhoneConnector $phoneConnector,
        protected LoggerInterface $logger,
        protected \Illuminate\Http\Request $request,
    ) {
        $this->guid = $this->request->input('Guid', $request->cookie('Guid'));
        $this->initializeClient();
    }

    /**
     * Initializes the client by setting up proxy configuration and configuring retry mechanisms.
     */
    private function initializeClient(): void
    {
        $this->initProxy();
        $this->configureClientRetries();
    }

    /**
     * Initializes the proxy settings for the application.
     *
     * This method checks if the proxy service is enabled. If enabled, it calls
     * the `applyProxySettings` method to set up the necessary proxy configurations.
     *
     * @return void
     */
    protected function initProxy(): void
    {
        if ($this->proxyService->isProxyEnabled()) {
            $this->applyProxySettings();
        }
    }

    /**
     * Applies proxy settings to the apple client service if available.
     *
     * This method retrieves the proxy option, fetches the proxy details from the proxy service,
     * and sets the proxy on the apple client if a valid proxy is obtained. Any exceptions during
     * this process are logged as errors.
     *
     * @return void
     */
    private function applyProxySettings(): void
    {
        try {

            $option = $this->getProxyOption();

            $proxy = $this->proxyService->getProxy($option);
            if ($proxy !== null) {
                $this->appleClient->withProxy($proxy->url);
            }

        } catch (Exception $e) {

            Log::error("AppleClientService proxy error $e");
        }
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
        if (!$this->proxyService->isIpaddressEnabled()) {
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

    /**
     * Configures the retry behavior for the apple client service.
     *
     * This method sets up the retry handling callback, the number of default retry attempts,
     * and the interval between each retry attempt for the apple client service.
     *
     * @return void
     */
    private function configureClientRetries(): void
    {
        $this->appleClient->setHandleRetry($this->handleRetryCallback());
        $this->appleClient->setTries(self::DEFAULT_RETRY_ATTEMPTS);
        $this->appleClient->setRetryInterval(self::RETRY_INTERVAL);
    }

    /**
     * Generates a closure to handle retry logic in case of request exceptions.
     *
     * This method creates a callback function designed to be used in retry mechanisms. It inspects the provided exception,
     * checking if it represents a connection issue. If so, it triggers a refresh of the proxy configuration from the
     * proxy service and reapplies it to the apple client before returning true to indicate a retry should occur. If the
     * exception does not relate to a connection problem, it returns false, suggesting no retry is needed.
     *
     * @return Closure A closure that decides whether to retry a request based on the nature of the exception.
     */
    protected function handleRetryCallback(): Closure
    {
        return function (FatalRequestException|RequestException $exception, Request $request) {
            if ($this->isConnectionException($exception)) {
                $this->appleClient->withProxy(
                    $this->proxyService->refreshProxy($this->getProxyOption())->url
                );

                return true;
            }

            return false;
        };
    }

    /**
     * Determines if the provided exception indicates a fatal connection issue.
     *
     * Inspects the given exception to ascertain whether it is an instance of
     * `FatalRequestException`, which would suggest a critical failure in the request handling.
     *
     * @param SaloonException $exception The exception to evaluate.
     *
     * @return bool True if the exception is a `FatalRequestException`, false otherwise.
     */
    protected function isConnectionException(SaloonException $exception): bool
    {
        return $exception instanceof FatalRequestException;
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
     * Retrieves the unique identifier (GUID) of the entity.
     *
     * This method returns the GUID associated with the current instance of the entity.
     * If no GUID is set, it returns null.
     *
     * @return string|null The unique identifier (GUID) or null if not set.
     */
    public function getGuid(): ?string
    {
        return $this->guid;
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
        return Cache::has("is_login:{$this->guid}");
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
        return Cache::put("is_login:{$this->guid}", Carbon::now(), self::LOGIN_CACHE_TTL);
    }

    /**
     * Retrieves the instance of the AppleClient.
     *
     * Returns the configured AppleClient instance which can be used for further interactions
     * with Apple services.
     *
     * @return AppleClient The configured instance of the AppleClient.
     */
    public function getAppleClient(): AppleClient
    {
        return $this->appleClient;
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
        return $this->appleClient->$name(...$parameters);
    }

}
