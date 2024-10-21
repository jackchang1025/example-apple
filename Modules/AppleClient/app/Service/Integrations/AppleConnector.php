<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Integrations;

use Modules\AppleClient\Service\AppleClient;
use Modules\AppleClient\Service\Config\HasConfig;
use Modules\AppleClient\Service\Cookies\CookieJarInterface;
use Modules\AppleClient\Service\Cookies\HasCookie;
use Modules\AppleClient\Service\Header\HasHeaderSynchronize;
use Modules\AppleClient\Service\Header\HasPersistentHeaders;
use Modules\AppleClient\Service\Helpers\Helpers;
use Modules\AppleClient\Service\Logger\Logger;
use Modules\AppleClient\Service\Proxy\HasProxy;
use Modules\AppleClient\Service\Response\Response;
use Modules\IpProxyManager\Service\ProxyService;
use Psr\Log\LoggerInterface;
use Saloon\Contracts\ArrayStore;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Request;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Saloon\Traits\Plugins\HasTimeout;


abstract class AppleConnector extends Connector
{
    use HasTimeout;
    use HasCookie;
    use HasHeaderSynchronize;
    use AlwaysThrowOnErrors;
    use HasPersistentHeaders;
    use Logger;
    use HasProxy;
    use HasConfig;
    use Helpers;
    use HasConfig {
        HasConfig::config as baseConfig;
    }

    public function __construct(protected AppleClient $apple)
    {
        $this->tries           = $this->apple->getTries();
        $this->retryInterval   = $this->apple->getRetryInterval();
        $this->throwOnMaxTries = $this->apple->getRetryWhenCallback();
    }

    public function getProxy(): ?ProxyService
    {
        return $this->proxy ?? $this->apple->getProxy();
    }

    public function getApple(): AppleClient
    {
        return $this->apple;
    }

    public function config(): ArrayStore
    {
        return $this->apple->config()
            ->merge($this->baseConfig()->all());
    }

    public function getHeaderRepositories(): ?ArrayStore
    {
        return $this->getHeaderRepositories ?? $this->apple->getHeaderRepositories();
    }

    public function getCookieJar(): ?CookieJarInterface
    {
        return $this->cookieJar ?? $this->apple->getCookieJar();
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger ?? $this->apple->getLogger();
    }

    public function resolveResponseClass(): string
    {
        return Response::class;
    }

    /**
     * @param Request         $request
     * @param MockClient|null $mockClient
     * @param callable|null   $handleRetry
     *
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     *
     * @return Response
     */
    public function send(Request $request, MockClient $mockClient = null, callable $handleRetry = null): Response
    {
        $this->middleware()->merge($this->apple->middleware());
        /**
         * @var Response $response
         */
        $response = parent::send($request, $mockClient, $handleRetry);

        return $response;
    }

    public function handleRetry(FatalRequestException|RequestException $exception, Request $request): bool
    {
        $handleRetry = $this->apple->getHandleRetry() ?? static fn(): bool => true;

       return $handleRetry($exception,$request);
    }
}
