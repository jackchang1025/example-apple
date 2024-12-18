<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Config;


trait HasConfig
{
    protected ?config $config = null;

    public function config(): config
    {
        return $this->config ??= new Config();
    }

    /**
     * 设置配置.
     *
     * @param config $config
     * @return $this
     */
    public function withConfig(config $config): static
    {
        $this->config = $config;
        return $this;
    }
}
