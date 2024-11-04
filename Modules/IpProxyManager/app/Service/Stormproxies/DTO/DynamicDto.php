<?php

namespace Modules\IpProxyManager\Service\Stormproxies\DTO;

use Modules\IpProxyManager\Service\BaseDto;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class DynamicDto extends BaseDto implements WithResponse
{
    use HasResponse;

    public function toQueryParameters(): array
    {
        return array_filter($this->data, static fn($value) => $value !== null);
    }
}
