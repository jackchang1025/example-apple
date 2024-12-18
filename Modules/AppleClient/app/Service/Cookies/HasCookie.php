<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Cookies;

use Modules\AppleClient\Service\Trait\HasPipelineExists;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

trait HasCookie
{
    use HasPipelineExists;

    protected ?CookieJarInterface $cookieJar = null;

    public function bootHasCookie(PendingRequest $pendingRequest): void
    {
//        if(!$this->requestPipelineExists($pendingRequest,'withCookieHeader') && $this->getAuthenticator() instanceof CookieAuthenticator){
//            $pendingRequest->getConnector()
//                ->middleware()
//                ->onRequest(function (PendingRequest $pendingRequest){
//                    $this->getAuthenticator()?->set($pendingRequest);
//
//                },'withCookieHeader');
//        }

        if (!$this->responsePipelineExists($pendingRequest, 'extractCookies') && $this->getAuthenticator(
            ) instanceof CookieAuthenticator) {
            $pendingRequest->getConnector()
                ->middleware()
                ->onResponse(
                    fn(Response $response) => $this->getAuthenticator()?->extractCookies($pendingRequest, $response),
                    'extractCookies'
                );
        }
    }

    public function withCookies(CookieJarInterface|array|null $cookies, bool $strictMode = false): static
    {
        if (is_array($cookies)) {
            $this->cookieJar = new CookieJar($strictMode, $cookies);
        } elseif ($cookies instanceof CookieJarInterface) {
            $this->cookieJar = $cookies;
        } else {
            $this->cookieJar = null;
        }

        return $this;
    }


    public function getCookieJar(): ?CookieJarInterface
    {
        return $this->cookieJar;
    }
}
