<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector;

use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Resources\AuthenticationResource;
use Modules\AppleClient\Service\Integrations\AppleConnector;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Request;

class AppleAuthenticationConnector extends AppleConnector
{
    protected bool $proxyEnabled = false;

    /**
     * @param Apple $apple
     * @param string $url
     */
    public function __construct(Apple $apple, protected string $url)
    {
        parent::__construct($apple);
    }

    public function resolveBaseUrl(): string
    {
        return $this->url;
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'zh-CN,en;q=0.9,zh;q=0.8',
        ];
    }

    public function handleRetry(FatalRequestException|RequestException $exception, Request $request): bool
    {
        return true;
    }

    public function getAuthenticationResource(): AuthenticationResource
    {
        return new AuthenticationResource($this);
    }
}
