<?php

namespace App\Http\Integrations\Proxy;

use App\Apple\Proxy\Exception\ProxyConfigurationNotFoundException;
use App\Apple\Proxy\Exception\ProxyModelNotFoundException;
use App\Http\Integrations\Proxy\HuaSheng\DTO\ExtractDto;
use App\Http\Integrations\Proxy\HuaSheng\HuaShengProxyConnector;
use App\Http\Integrations\Proxy\HuaSheng\Requests\Extract;
use App\Http\Integrations\Proxy\Stormproxies\Request\AccountPasswordMode;
use App\Http\Integrations\Proxy\Stormproxies\Request\DynamicMode;
use App\Http\Integrations\Proxy\Stormproxies\StormProxyConnector;
use App\Models\ProxyConfiguration as ProxyConfigurationModel;
use Illuminate\Support\Arr;

class ProxyConfiguration
{
    protected ?array $config = null;

    // 驱动映射
    protected array $driverMap = [
        'hailiangip' => 'App\Apple\Proxy\Driver\Hailiangip\HailiangipProxy',
        'stormproxies' => StormProxyConnector::class,
        'huashengdaili' => HuaShengProxyConnector::class,
    ];

    // 模式映射
    protected array $modeMap = [
        'hailiangip' => [
            'flow' => 'App\Apple\Proxy\Driver\Hailiangip\FlowProxy',
            'dynamic' => 'App\Apple\Proxy\Driver\Hailiangip\DynamicProxy',
        ],
        'stormproxies' => [
            'flow' => AccountPasswordMode::class,
            'dynamic' => DynamicMode::class,
        ],
        'huashengdaili' => [
            'api' => Extract::class,
        ],
    ];

    public function hasDriver(string $driver): bool
    {
        return isset($this->driverMap[$driver]);
    }

    public function getConfigs(): array
    {
        if ($this->config === null) {
            $this->config = ProxyConfigurationModel::where('is_active', true)
                ->firstOrFail()
                ->configuration;
        }
        return $this->config;
    }

    public function getConfig(string $key, $default = null): mixed
    {
        return Arr::get($this->getConfigs(), $key, $default);
    }

    public function getDefaultDriver(): string
    {
        return $this->getConfig('default_driver');
    }

    public function getDefaultDriverConfig(): array
    {
        return $this->getDriverConfig($this->getDefaultDriver());
    }

    /**
     * @throws ProxyConfigurationNotFoundException
     */
    public function getDriverConfig(string $driver): array
    {
        $config = $this->getConfig($driver);
        if ($config === null) {
            throw new ProxyConfigurationNotFoundException("Configuration for driver {$driver} not found.");
        }
        return $config;
    }

    public function getDefaultDriverClass(): string
    {
        $defaultDriver = $this->getDefaultDriver();
        return $this->getDriverClass($defaultDriver);
    }

    public function getDefaultModeClass(): string
    {
        $defaultDriver = $this->getDefaultDriver();
        $defaultDriverConfig = $this->getDefaultDriverConfig();
        if (empty($defaultMode = $defaultDriverConfig['mode'])){
            throw new ProxyModelNotFoundException("Mode for driver {$defaultDriver} not found in driver config.");
        }
        return $this->getModeClass($defaultDriver, $defaultMode);
    }

    public function getDriverClass(string $driver): string
    {
        if (!isset($this->driverMap[$driver])) {
            throw new ProxyConfigurationNotFoundException("Driver {$driver} not found in driver map.");
        }
        return $this->driverMap[$driver];
    }

    public function getModeClass(string $driver, string $mode): string
    {
        if (!isset($this->modeMap[$driver][$mode])) {
            throw new ProxyModelNotFoundException("Mode {$mode} for driver {$driver} not found in mode map.");
        }
        return $this->modeMap[$driver][$mode];
    }

}
