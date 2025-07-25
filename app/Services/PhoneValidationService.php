<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Phone;
use App\Enums\PhoneStatus;
use App\Services\Integrations\Phone\Exception\InvalidPhoneException;
use Illuminate\Support\Facades\Log;
use Saloon\Http\Response;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Exceptions\Request\FatalRequestException;
use Illuminate\Support\Collection;

/**
 * 手机号验证服务
 */
class PhoneValidationService
{
    private array $validationResults = [];

    /**
     * 验证单个手机号是否失效
     */
    public function validatePhone(Phone $phone): bool
    {
        try {
            $phoneRequest = $phone->makePhoneRequest();
            $phoneRequest->code();

            return false; // 成功获取验证码，手机号有效

        } catch (InvalidPhoneException $e) {
            Log::info("手机号失效", ['phone_id' => $phone->id, 'phone' => $phone->phone]);
            return true; // 手机号失效

        } catch (\Exception $e) {
            Log::warning("手机号验证异常", [
                'phone_id' => $phone->id,
                'phone' => $phone->phone,
                'error' => $e->getMessage()
            ]);
            return false; // 其他异常，不标记为失效
        }
    }

    /**
     * 并发验证多个手机号
     */
    public function validatePhonesConcurrently(Collection $phones, int $concurrency = 10): array
    {
        $this->validationResults = [
            'total' => $phones->count(),
            'invalid_count' => 0,
            'valid_count' => 0,
            'error_count' => 0
        ];

        if ($phones->isEmpty()) {
            return $this->validationResults;
        }

        // 创建连接器
        $connector = new \App\Services\Integrations\Phone\PhoneConnector();

        // 使用Saloon池进行并发验证
        $pool = $connector->pool();

        // 设置并发数
        $pool->setConcurrency($concurrency);

        // 添加请求到池中
        $requests = $this->generatePoolablePhoneRequests($phones);
        $pool->setRequests($requests);

        // 处理成功响应
        $pool->withResponseHandler(function (Response $response, string|int $phoneId) {
            $phone = Phone::find($phoneId);
            if ($phone) {
                // 检查响应是否包含错误码10022
                if ($response->json('code') === 10022) {
                    $this->markAsInvalid($phone);
                    $this->validationResults['invalid_count']++;
                    Log::info("手机号失效", ['phone_id' => $phone->id, 'phone' => $phone->phone]);
                } else {
                    $this->validationResults['valid_count']++;
                    Log::info("手机号验证成功", ['phone_id' => $phone->id, 'phone' => $phone->phone]);
                }
            }
        });

        // 处理失败响应
        $pool->withExceptionHandler(function (FatalRequestException|RequestException $exception, string $phoneId) {
            $phone = Phone::find($phoneId);
            if ($phone) {
                $this->validationResults['error_count']++;
                Log::warning("手机号验证异常", [
                    'phone_id' => $phone->id,
                    'phone' => $phone->phone,
                    'error' => $exception->getMessage()
                ]);
            }
        });

        // 发送并等待所有请求完成
        $promise = $pool->send();
        $promise->wait();

        return $this->validationResults;
    }

    /**
     * 生成可池化的手机请求
     */
    private function generatePoolablePhoneRequests(Collection $phones): \Generator
    {
        foreach ($phones as $phone) {
            yield $phone->id => new \App\Services\Integrations\Phone\PoolablePhoneRequest($phone->phone_address);
        }
    }



    /**
     * 将手机号标记为失效
     */
    public function markAsInvalid(Phone $phone): void
    {
        if ($phone->status !== PhoneStatus::INVALID->value) {
            $phone->update(['status' => PhoneStatus::INVALID->value]);

            Log::info("手机号已标记为失效", [
                'phone_id' => $phone->id,
                'phone' => $phone->phone
            ]);
        }
    }

    /**
     * 获取需要验证的手机号查询构建器
     */
    public function getValidatablePhones(bool $forceAll = false): \Illuminate\Database\Eloquent\Builder
    {
        $query = Phone::query()
            ->whereNotNull('phone_address')
            ->where('phone_address', '!=', '');

        if (!$forceAll) {
            $query->where('status', PhoneStatus::NORMAL->value);
        } else {
            $query->whereIn('status', [PhoneStatus::NORMAL->value, PhoneStatus::INVALID->value]);
        }

        return $query;
    }
}
