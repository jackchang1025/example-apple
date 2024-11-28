<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Integrations\AppleAuth;

use Modules\AppleClient\Service\Integrations\AppleAuth\Resources\SignInResources;
use Modules\AppleClient\Service\Integrations\AppleConnector;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;

class AppleAuthConnector extends AppleConnector
{
    protected bool $proxyEnabled = false;

    public function boot(PendingRequest $pendingRequest): void
    {
        if (empty($this->apple->config()->get('apple_auth')['url'])) {
            throw new \InvalidArgumentException('apple_auth config is empty');
        }
    }

    public function resolveBaseUrl(): string
    {
        return $this->apple->config()->get('apple_auth')['url'];
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

    public function getSignInResources(): SignInResources
    {
        return new SignInResources($this);
    }
}
