<?php

namespace Modules\IpProxyManager\Service\Smartdaili\DTO;

use Modules\IpProxyManager\Service\BaseDto;

class ProxyDto extends BaseDto
{
    protected array $fillable = [
        'username',
        'password',
        'endpoint',
        'port',
        'protocol',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    public function toQueryParameters(): array
    {
        return [];
    }
}
