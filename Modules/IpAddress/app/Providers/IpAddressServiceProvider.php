<?php

namespace Modules\IpAddress\Providers;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;

class IpAddressServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'PhoneCode';

    protected string $nameLower = 'phonecode';
}
