<?php

namespace Modules\IpProxyManager\Service;

use App\Models\ProxyConfiguration;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Manager;
use Modules\IpAddress\Service\IpService;
use Modules\IpProxyManager\Service\Exception\ProxyConfigurationNotFoundException;
use Modules\IpProxyManager\Service\Exception\ProxyModelNotFoundException;
use Psr\Log\LoggerInterface;

class ProxyManager extends Manager
{
    /**
     * @var IpService
     */
    protected IpService $ipService;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var array|null
     */
    protected ?array $proxyConfiguration = null;

    /**
     * Create a new manager instance.
     */
    public function __construct(Container $container, IpService $ipService, LoggerInterface $logger)
    {
        parent::__construct($container);
        $this->ipService = $ipService;
        $this->logger    = $logger;
    }

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return data_get($this->getActiveConfiguration(), 'default_driver')
            ?? $this->config->get('ipproxymanager.default');
    }

    /**
     * Get active configuration from database
     */
    protected function getActiveConfiguration(): array
    {
        if ($this->proxyConfiguration) {
            return $this->proxyConfiguration;
        }

        $config = ProxyConfiguration::where('is_active', true)->first();

        if (!$config) {
            throw new ProxyConfigurationNotFoundException("No active proxy configuration found");
        }

        // 提取公共配置
        $commonConfig = Arr::Only($config->toArray(), [
            'ipaddress_enabled',
            'proxy_enabled',
        ]);

        // 获取驱动配置
        $configuration = $config->configuration;

        $defaultDriver = $configuration['default_driver'];
        unset($configuration['default_driver']);

        // 为每个驱动合并公共配置
        foreach ($configuration as $driver => $driverConfig) {
            $configuration[$driver] = array_merge(array_filter($driverConfig, fn($value) => !is_null($value)),
                $commonConfig);
        }

        // 重新添加默认驱动配置
        $configuration['default_driver'] = $defaultDriver;

        $this->proxyConfiguration = array_merge($configuration, $config->makeHidden(['configuration'])->toArray());

        return $this->proxyConfiguration;
    }

    /**
     * Create an instance of the specified driver.
     *
     * @throws ProxyConfigurationNotFoundException
     * @throws ProxyModelNotFoundException
     */
    protected function createDriver($driver = null): ProxyService
    {
        $config = $this->getActiveConfiguration();

        if ($driver !== null) {
            // 如果指定了驱动，使用活动配置中该驱动的配置
            if (!isset($config[$driver])) {
                throw new ProxyConfigurationNotFoundException("Driver {$driver} not configured");
            }

            // 修改驱动但保持其他配置
            $config['default_driver'] = $driver;
        }

        $driver       = $config['default_driver'];
        $driverConfig = $config[$driver] ?? [];

        if (empty($driver)) {
            throw new ProxyConfigurationNotFoundException("Driver is not empty");
        }

        // 检查并获取模式
        if (empty($driverConfig['mode'])) {
            throw new ProxyModelNotFoundException("Mode not configured for driver {$driver}");
        }
        $mode = $driverConfig['mode'];

        // 获取驱动配置
        $providerConfig = $this->config->get("ipproxymanager.providers.{$driver}");
        if (!$providerConfig) {
            throw new ProxyConfigurationNotFoundException("Driver {$driver} not found in config");
        }

        // 获取模式配置
        $modeConfig = $providerConfig['mode'][$mode] ?? null;
        if (!$modeConfig) {
            throw new ProxyModelNotFoundException("Mode {$mode} not found for driver {$driver}");
        }

        // 合并模式默认配置和驱动配置
        $mergedConfig = array_merge(
            $modeConfig['default_config'] ?? [],
            $driverConfig
        );

        // 创建组件
        $dto       = new $modeConfig['dto']($mergedConfig);
        $request   = new $modeConfig['request']($dto);
        $connector = new $providerConfig['connector']();

        return new ProxyService($connector, $this->logger, $this->ipService, $request);
    }

    /**
     * Forget all of the resolved driver instances.
     */
    public function forgetDrivers(): self
    {
        parent::forgetDrivers();
        $this->proxyConfiguration = null;

        return $this;
    }

    /**
     * Create a proxy service instance.
     */
    public function connector(?string $driver = null): ProxyService
    {
        return $this->driver($driver);
    }
}
