<?php

namespace Modules\AppleClient\Service\Middleware;

use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\Authenticate\Authenticate;
use Modules\AppleClient\Service\Integrations\Icloud\IcloudConnector;
use Modules\AppleClient\Service\Integrations\Icloud\Request\AuthenticateRequest;
use Modules\AppleClient\Service\Integrations\Icloud\Request\LoginDelegatesRequest;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;
use Modules\AppleClient\Service\Apple;

readonly class AuthenticateMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Apple $apple,
        private CacheInterface $cache,
    ) {
    }

    public function onRequest(PendingRequest $pendingRequest): PendingRequest
    {
        $connector = $pendingRequest->getConnector();
        $request   = $pendingRequest->getRequest();

        if ($connector instanceof IcloudConnector && !$request instanceof AuthenticateRequest && !$request instanceof LoginDelegatesRequest) {

            /**
             * @var BasicAuthenticator $authenticate
             */
            $authenticate = $connector->getAuthenticator();

            if (!$authenticate) {
                $cacheKey = sprintf(
                    '%s_%s',
                    $this->apple->getConfig()->get('cache.prefix.authenticate', 'authenticate'),
                    $this->apple->getAccount()->getSessionId()
                );

                $authenticate = $this->cache->get($cacheKey);

                if (!$authenticate) {
                    throw new RuntimeException('Authenticate not found');
                }

                if (is_array($authenticate)) {
                    $authenticate = Authenticate::from($authenticate);
                }

                if (!$authenticate instanceof Authenticate) {
                    throw new RuntimeException('Authenticate data format error');
                }

                if (!$authenticate->appleAccountInfo->dsid || !$authenticate->tokens->mmeAuthToken) {
                    throw new RuntimeException('Authenticate data format error');
                }

                (new BasicAuthenticator(
                    $authenticate->appleAccountInfo->dsid,
                    $authenticate->tokens->mmeAuthToken
                ))->set($pendingRequest);

            }
        }

        return $pendingRequest;
    }

    public function onResponse(Response $response): Response
    {
        return $response;
    }
}
