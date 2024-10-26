<?php

namespace Modules\IpProxyManager\Service\HuaSheng\Dto;

use Modules\IpProxyManager\Service\BaseDto;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class ExtractDto extends BaseDto implements WithResponse
{
    use HasResponse;

    public function toQueryParameters(): array
    {
        return array_filter($this->data,static fn($value) => $value !== null);
    }
}
