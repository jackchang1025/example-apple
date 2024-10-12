<?php

namespace App\Proxy;

use App\Models\ProxyConfiguration;
use App\Proxy\Exception\ProxyConfigurationNotFoundException;
use App\Proxy\Exception\ProxyModelNotFoundException;
use Psr\Log\LoggerInterface;
use Weijiajia\IpProxyManager\HuaSheng\Dto\ExtractDto;
use Weijiajia\IpProxyManager\HuaSheng\HuaShengConnector;
use Weijiajia\IpProxyManager\HuaSheng\Requests\ExtractRequest;
use Weijiajia\IpProxyManager\Stormproxies\DTO\AccountPasswordDto;
use Weijiajia\IpProxyManager\Stormproxies\DTO\DynamicDto;
use Weijiajia\IpProxyManager\Stormproxies\Request\AccountPasswordRequest;
use Weijiajia\IpProxyManager\Stormproxies\Request\DynamicRequest;
use Weijiajia\IpProxyManager\Stormproxies\StormConnector;

class ProxyFactory
{
    public function __construct(protected  LoggerInterface $logger)
    {
    }

    /**
     * Create a proxy connector based on the configuration.
     *
     * @param ProxyConfiguration|null $config
     * @return ProxyService
     * @throws ProxyConfigurationNotFoundException
     * @throws ProxyModelNotFoundException
     */
    public function create(?ProxyConfiguration $config = null): ProxyService
    {
        $config = $config ?: ProxyConfiguration::where('is_active', true)->first();

        $driver = $config['configuration']['default_driver'];
        $mode = $config['configuration'][$driver]['mode'];

        if (empty($driver) || empty($mode)){
            throw new ProxyConfigurationNotFoundException("driver or mode is not empty");
        }
        $configuration = $config['configuration'][$driver] ?? [];

        $configuration = array_merge($configuration,$config->makeHidden(['configuration'])->toArray());

        return match ($driver) {
            'hailiangip' => $this->createHailiangipConnector($configuration, $mode),
            'stormproxies' => $this->createStormProxiesConnector($configuration, $mode),
            'huashengdaili' => $this->createHuaShengConnector($configuration, $mode),
            default => throw new ProxyConfigurationNotFoundException("Unsupported driver: $driver"),
        };
    }

    protected function createHailiangipConnector(array $config, ?string $mode): ProxyService
    {
        // 这里可以根据 mode 进行不同的配置
        throw new ProxyModelNotFoundException("Unsupported driver: HailiangipConnector");
    }

    protected function createStormProxiesConnector(array $config, ?string $mode): ProxyService
    {
        $request =  match ($mode) {
            'flow' => $this->initializeFlowConnector($config),
            'dynamic' => $this->initializeDynamicConnector($config),
            default => throw new ProxyModelNotFoundException("Unsupported model: $mode"),
        };

        return new ProxyService(
            new StormConnector(),
            $this->logger,
            $request,
        );
    }

    protected function initializeFlowConnector(array $config): AccountPasswordRequest
    {
        return new AccountPasswordRequest(new AccountPasswordDto($config));
    }

    protected function initializeDynamicConnector(array $config): DynamicRequest
    {
        return new DynamicRequest(new DynamicDto($config));
    }

    protected function createHuaShengConnector(array $config, string $mode = 'api'): ProxyService
    {
        if ($mode !== 'api') {
            throw new ProxyModelNotFoundException("Unsupported model: $mode");
        }

        return new ProxyService(
            new HuaShengConnector(),
            $this->logger,
            new ExtractRequest(new ExtractDto($config)),
        );
    }


}