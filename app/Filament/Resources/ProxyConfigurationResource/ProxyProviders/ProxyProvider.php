<?php

namespace App\Filament\Resources\ProxyConfigurationResource\ProxyProviders;

use Filament\Forms\Components\Component;

abstract class ProxyProvider
{
    /**
     * Returns the key for the proxy provider.
     *
     * @return string
     */
    abstract public static function getKey(): string;

    /**
     * Returns the name of the proxy provider.
     *
     * @return string
     */
    abstract public static function getName(): string;

    /**
     * Returns the schema for the proxy provider.
     *
     * @return array<Component>
     */
    abstract public static function getFields(): array;
}
