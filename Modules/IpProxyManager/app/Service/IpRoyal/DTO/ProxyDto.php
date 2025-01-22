<?php

namespace Modules\IpProxyManager\Service\IpRoyal\DTO;

use Modules\IpProxyManager\Service\BaseDto;
use Saloon\Contracts\DataObjects\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class ProxyDto extends BaseDto implements WithResponse
{
    use HasResponse;

    public function toQueryParameters(): array
    {
        $params = array_filter($this->data, static fn($value) => $value !== null);

        // 处理特殊参数
        if (isset($params['sticky_session']) && $params['sticky_session']) {
            $params['session_id'] = $params['session_id'] ?? uniqid('iproyal_', true);
        }

        return $params;
    }
} 