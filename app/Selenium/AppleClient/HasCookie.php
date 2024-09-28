<?php

namespace App\Selenium\AppleClient;

use App\Selenium\Cookie\CookieJar;
use App\Selenium\PendingRequest;
use Facebook\WebDriver\Cookie;
use Hyperf\Collection\Collection;

trait HasCookie
{
    protected ?CookieJar $cookieJar = null;

    public function setCookieJar(?CookieJar $cookieJar): void
    {
        $this->cookieJar = $cookieJar;
    }

    public function getCookieJar(): CookieJar
    {
        return $this->cookieJar??= new CookieJar();
    }

    public function createFromArray(array $cookies): Collection
    {
        return $this->getCookieJar()->createFromArray($cookies);
    }

    public function bootHasCookie(PendingRequest $pendingRequest):void
    {
        $pendingRequest->getConnector()->addMiddleware(function (PendingRequest $pendingRequest,\Closure $next) {

            $connector = $pendingRequest->getConnector();

            $this->getCookieJar()->getCookies()->map(fn (Cookie $cookie) =>  $connector->client()->manage()->addCookie($cookie));

            $response = $next($pendingRequest);

            $cookies = $connector->client()->manage()->getCookies();

            $this->getCookieJar()->mergeCookies($cookies);

            return $response;
        });
    }
}
