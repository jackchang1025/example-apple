<?php

namespace Modules\IpProxyManager\Service\Stormproxies\DTO;

use Modules\IpProxyManager\Service\BaseDto;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class DynamicDto extends BaseDto implements WithResponse
{
    use HasResponse;

    protected array $data = [
        'ep'       => 'hk',
        'app_key'  => null,
        'cc'       => 'cn',
        'num'      => 1,
        'city'     => null,
        'state'    => null,
        'life'     => 1,
        'protocol' => 'http',
        'format'   => 2,
        'lb'       => 1,
    ];

    public function __construct(array $data = [])
    {
        parent::__construct(array_merge($this->data, $data));
    }

    public function toQueryParameters(): array
    {
        return array_filter($this->data, static fn($value) => $value !== null);
    }
}
