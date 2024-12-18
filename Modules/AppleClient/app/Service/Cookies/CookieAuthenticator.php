<?php

namespace Modules\AppleClient\Service\Cookies;

use Saloon\Contracts\Authenticator;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

readonly class CookieAuthenticator implements Authenticator
{
    public function __construct(
        private CookieJarInterface $cookieJar
    ) {
    }

    public function getCookieJar(): CookieJarInterface
    {
        return $this->cookieJar;
    }

    public function set(PendingRequest $pendingRequest): void
    {
        $this->cookieJar->withCookieHeader($pendingRequest);
    }

    public function extractCookies(PendingRequest $request, Response $response): void
    {
        $this->cookieJar->extractCookies($request, $response);
    }
}
