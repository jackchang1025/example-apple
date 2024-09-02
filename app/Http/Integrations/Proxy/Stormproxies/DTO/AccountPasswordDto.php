<?php

namespace App\Http\Integrations\Proxy\Stormproxies\DTO;

use App\Http\Integrations\Proxy\BaseDto;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class AccountPasswordDto extends BaseDto implements WithResponse
{
    use HasResponse;

    protected array $data = [
        'session' => null,
        'life'    => 1,
        'area'    => null,
        'city'    => null,
        'state'   => null,
        'ip'      => null,
    ];

    public function __construct(array $data = [])
    {
        parent::__construct(array_merge($this->data, $data));
    }

    public function toQueryParameters(): array
    {
        return array_filter($this->data, fn($value) => $value !== null);
    }
}
