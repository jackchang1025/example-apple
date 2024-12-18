<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\AppleClient\Service\Config\Config;
use Modules\AppleClient\Service\Cookies\HasCookie;
use Modules\AppleClient\Service\DataConstruct\Account;
use Modules\AppleClient\Service\Header\HasHeaderSynchronize;
use Modules\AppleClient\Service\Helpers\Helpers;
use Modules\AppleClient\Service\Proxy\HasProxy;
use Modules\AppleClient\Service\Resources\Web\WebResource;
use Modules\AppleClient\Service\Trait\HasLogger;
use Modules\AppleClient\Service\Trait\HasTries;
use Psr\Log\LoggerInterface;
use Saloon\Traits\Conditionable;
use Saloon\Traits\Macroable;
use Saloon\Traits\RequestProperties\HasMiddleware;
use Modules\AppleClient\Service\Resources\Api\ApiResource;
use Saloon\Traits\HasMockClient;
use Saloon\Helpers\MiddlewarePipeline;

class Apple
{
    use Macroable;
    use HasProxy;

//    use HasHeaderSynchronize;
    use Helpers;
    use HasLogger;
    use Conditionable;
    use HasTries;
    use HasMiddleware;
    use HasMockClient;

    protected ?WebResource $webResource = null;
    protected ?ApiResource $apiResource = null;

    public function __construct(
        protected Account $account,
        protected Config $config,
        protected ?Dispatcher $dispatcher = null
    ) {

    }

    public function withMiddleware(MiddlewarePipeline $middleware): self
    {
        $this->middlewarePipeline = $middleware;

        return $this;
    }

    public function withDispatcher(Dispatcher $dispatcher): self
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function withConfig(Config|array $config): static
    {
        if (is_array($config)) {
            $this->config = new Config($config);

            return $this;
        }

        $this->config = $config;

        return $this;
    }

    public function getDispatcher(): ?Dispatcher
    {
        return $this->dispatcher;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function getWebResource(): WebResource
    {
        return $this->webResource ??= new WebResource($this);
    }

    public function getApiResources(): ApiResource
    {
        return $this->apiResource ??= new ApiResource($this);
    }

    public function getAccount(): Account
    {
        return $this->account;
    }
}
