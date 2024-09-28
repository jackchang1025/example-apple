<?php

namespace App\Selenium\Trait;



use App\Selenium\Contract\ArrayStoreContract;
use App\Selenium\Repositories\ArrayStore;

trait HasConfig
{
    /**
     * Request Config
     */
    protected ArrayStoreContract $config;

    /**
     * Access the config
     */
    public function config(): ArrayStoreContract
    {
        return $this->config ??= new ArrayStore($this->defaultConfig());
    }

    public function withConfig(ArrayStoreContract $config): ArrayStoreContract
    {
        return $this->config = $config;
    }

    /**
     * Default Config
     *
     * @return array<string, mixed>
     */
    protected function defaultConfig(): array
    {
        return [];
    }
}
