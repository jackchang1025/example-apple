<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Integrations\AppleAuth;

use Modules\AppleClient\Service\AppleAuth;
use Modules\AppleClient\Service\Integrations\AppleConnector;
use Saloon\Http\PendingRequest;

class AppleAuthConnector extends AppleConnector
{
    use AppleAuth;
    protected bool $proxyEnabled = false;

    public function boot(PendingRequest $pendingRequest): void
    {
        if (empty($this->apple->config()->get('apple_auth')['url'])) {
            throw new \InvalidArgumentException('apple_auth config is empty');
        }
    }

    public function getAppleAuthConnector(): AppleAuthConnector
    {
        return $this;
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
}
