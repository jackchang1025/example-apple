<?php

declare(strict_types=1);

namespace App\Services\Integrations\Phone;

use Saloon\Http\Connector;

/**
 * 手机验证连接器
 * 用于支持Saloon的并发池功能
 */
class PhoneConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return 'http://localhost'; // 基础URL，实际URL在请求中定义
    }
} 