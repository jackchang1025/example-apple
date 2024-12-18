<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Integrations;

use Illuminate\Support\Str;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\Cookies\CookieJarInterface;
use Modules\AppleClient\Service\Cookies\HasCookie;
use Modules\AppleClient\Service\Header\HasHeaderSynchronize;
use Modules\AppleClient\Service\Header\HasPersistentHeaders;
use Modules\AppleClient\Service\Header\HeaderSynchronizeInterface;
use Modules\AppleClient\Service\Helpers\Helpers;
use Modules\AppleClient\Service\Proxy\HasProxy;
use Modules\AppleClient\Service\Response\Response;
use Modules\AppleClient\Service\Trait\HasLogger;
use Modules\AppleClient\Service\Trait\HasPipelineExists;
use Modules\IpProxyManager\Service\ProxyService;
use Psr\Log\LoggerInterface;
use Saloon\Contracts\Authenticator;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Saloon\Traits\Plugins\HasTimeout;


abstract class AppleConnector extends Connector
{
    use HasTimeout;
    use HasHeaderSynchronize;
    use AlwaysThrowOnErrors;
    use HasPersistentHeaders;
    use HasLogger;
    use HasProxy;
    use Helpers;
    use HasPipelineExists;
    use HasCookie;

    public function __construct(
        protected Apple $apple,
        ?Authenticator $authenticator = null,
        ?HeaderSynchronizeInterface $headerSynchronize = null
    ) {
        //设置重试次数
        $this->tries = $this->apple->getTries();

        //设置重试间隔
        $this->retryInterval = $this->apple->getRetryInterval();

        //设置是否使用指数退避
        $this->useExponentialBackoff = $this->apple->getUseExponentialBackoff();

        $this->middleware()->merge($this->apple->middleware());

        $this->authenticator = $authenticator;

        $this->headerRepositories = $headerSynchronize;
    }

    public function getProxy(): ?ProxyService
    {
        return $this->proxy ?? $this->apple->getProxy();
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger ?? $this->apple->getLogger();
    }

    protected function defaultConfig(): array
    {
        return $this->apple->config()?->all() ?? [];
    }

    public function resolveResponseClass(): string
    {
        return Response::class;
    }

    public function connectTimeout(): int
    {
        return $this->apple->config()->get('connectTimeout', 60);
    }

    public function getMockClient(): ?MockClient
    {
        return $this->mockClient ?? $this->apple->getMockClient();
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
        /**
         * @var Response $response
         */
        $response = parent::send($request, $mockClient, $handleRetry);

        return $response;
    }

    public function handleRetry(FatalRequestException|RequestException $exception, Request $request): bool
    {
        logger('handleRetry', ['exception' => $exception]);

        $handleRetry = $this->apple->getHandleRetry() ?? static fn(): bool => true;

       return $handleRetry($exception,$request);
    }

    protected function formatRequestLog(PendingRequest $request): ?PendingRequest
    {
        $this->getLogger()?->debug('request', [
            'method'  => $request->getMethod(),
            'uri'     => (string)$request->getUri(),
            'config'  => $request->config()->all(),
            'headers' => $request->headers()->all(),
            'body'    => $request->body()?->all(),
        ]);

        return $request;
    }

    protected function formatResponseLog(\Saloon\Http\Response $response): ?\Saloon\Http\Response
    {
        $body = trim($response->body());

        if (Str::length($body) > 2000) {
            $body = Str::substr($body, 0, 2000);
        }

        $this->getLogger()?->debug('response', [
            'status'  => $response->status(),
            'headers' => $response->headers()->all(),
            'body'    => $body,
        ]);

        return $response;
    }
}
