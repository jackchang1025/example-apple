<?php

declare(strict_types=1);

namespace App\Services\Integrations\Phone;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use App\Services\Integrations\Phone\Exception\InvalidPhoneException;

/**
 * 可池化的手机验证请求
 */
class PoolablePhoneRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private string $phoneAddress)
    {
        if (!preg_match('/^https?:\/\//i', $this->phoneAddress)) {
            $this->phoneAddress = 'http://' . $this->phoneAddress;
        }
    }

    public function resolveEndpoint(): string
    {
        return $this->phoneAddress;
    }

    /**
     * 解析验证码
     */
    public function parseCode(string $responseBody): ?string
    {
        if (preg_match('/\b\d{6}\b/', $responseBody, $matches)) {
            return $matches[0];
        }

        return null;
    }
}
