<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Proxy;

use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Modules\IpProxyManager\Service\ProxyService;
use Saloon\Http\PendingRequest;

trait HasProxy
{
    protected bool $proxyEnabled = true;

    public function isProxyEnabled(): bool
    {
        return $this->proxyEnabled;
    }

    public function setProxyEnabled(bool $proxyEnabled): void
    {
        $this->proxyEnabled = $proxyEnabled;
    }

    protected ?ProxyService $proxy = null;

    public function bootHasProxy(PendingRequest $pendingRequest): void
    {
        if ($this->isProxyEnabled() && $this->getProxy()?->isProxyEnabled()) {

            $proxyUrl = $this->getProxy()->getProxy()?->url;
            if ($proxyUrl !== null && !$this->isValidProxyUrl($proxyUrl)) {
                throw new InvalidArgumentException("Invalid proxy URL: $proxyUrl");
            }

            $pendingRequest->config()
                ->add(RequestOptions::PROXY, $proxyUrl);
        }
    }

    public function getProxy(): ?ProxyService
    {
        return $this->proxy;
    }

    public function withProxy(?ProxyService $proxy = null): static
    {
        $this->proxy = $proxy;

        return $this;
    }

    protected function isValidProxyUrl(string $url): bool
    {
        // Regular expression pattern to match a valid URL
        // Updated regular expression pattern to match valid URLs including IP addresses
        $pattern = '/^(http|https):\/\/'
            . '('
            . '(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])'
            . '|'
            . '((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)'
            . '|'
            . '\[[0-9a-fA-F:]+\]' // IPv6
            . '|'
            . 'localhost'
            . ')'
            . '(:[0-9]{1,5})?'
            . '(\/.*)?$/i';

        return (bool) preg_match($pattern, $url);
    }
}
