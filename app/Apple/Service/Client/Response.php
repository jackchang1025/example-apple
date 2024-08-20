<?php

namespace App\Apple\Service\Client;

use App\Apple\Service\DataConstruct\Phone;
use App\Apple\Service\DataConstruct\ServiceError;
use App\Apple\Service\DOMDocument\DOMDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Http\Client\Response as HttpClientResponse;

/**
 * @mixin HttpClientResponse
 */

class Response
{
    use Macroable {
        __call as macroCall;
    }

    protected ?array $jsonData = null;

    protected readonly DOMDocument $document;

    /**
     * @param HttpClientResponse $response HTTP 响应对象
     */
    public function __construct(
        protected HttpClientResponse $response,
    ) {
        $this->document = new DOMDocument($response->body());
    }

    public function getDocument(): DOMDocument
    {
        return $this->document;
    }

    public function hasTrustedDevices():bool
    {
        return $this->getJsonData()['direct']['hasTrustedDevices'] ?? false;
    }

    /**
     * 获取第一个服务错误
     */
    public function getFirstError(): ?ServiceError
    {
        return $this->getServiceErrors()->first();
    }

    /**
     * 获取第一个服务错误的消息
     */
    public function getFirstErrorMessage(): ?string
    {
        return $this->getFirstError()?->getMessage() ?? null;
    }

    /**
     * 获取电话号码信息
     */
    public function getPhoneNumber(): ?Phone
    {
        $data = $this->json('phoneNumber');

        return $data ? new Phone($data) : null;
    }

    /**
     * 获取安全码信息
     */
    public function getSecurityCode(): array
    {
        return $this->json('securityCode', []);
    }

    /**
     * 检查是否为 HSA2 账户
     */
    public function isHsa2Account(): bool
    {
        return $this->json('hsa2Account', false);
    }

    /**
     * 检查是否为受限账户
     */
    public function isRestrictedAccount(): bool
    {
        return $this->json('restrictedAccount', false);
    }

    /**
     * 检查是否支持恢复
     */
    public function supportsRecovery(): bool
    {
        return $this->json('supportsRecovery', false);
    }

    /**
     * 获取电话号码验证信息
     */
    public function phoneNumberVerification(): ?array
    {
        return $this->json('phoneNumberVerification');
    }

    /**
     * 获取无法使用电话号码的 URL
     */
    public function getCantUsePhoneNumberUrl(): ?string
    {
        return $this->json('cantUsePhoneNumberUrl');
    }

    public function service_errors(): Collection
    {
        return collect($this->json('service_errors',[]))
            ->map(fn(array $serviceErrors) => new ServiceError($serviceErrors));
    }

    public function service_errors_first(): ?ServiceError
    {
        return $this->service_errors()->first();
    }

    public function validationErrors(): Collection
    {
        return collect($this->json('validationErrors',[]))
            ->map(fn(array $serviceErrors) => new ServiceError($serviceErrors));
    }

    public function validationErrorsFirst(): ?ServiceError
    {
        return $this->validationErrors()->first();
    }

    public function hasError():bool
    {
        return $this->json('hasError', false);
    }

    /**
     * 获取所有信任的电话号码
     *
     * @return Collection
     */
    public function getTrustedPhoneNumbers(): Collection
    {
        return collect($this->getJsonData()['direct']['twoSV']['phoneNumberVerification']['trustedPhoneNumbers'] ?? [])
            ->map(fn(array $phone) => new Phone($phone));
    }

    public function getAuthServiceErrors(): Collection
    {
        return collect($this->getJsonData()['direct']['twoSV']['phoneNumberVerification']['serviceErrors'] ?? [])
            ->map(fn(array $serviceErrors) => new ServiceError($serviceErrors));
    }

    public function getServiceErrors(): Collection
    {
        return collect($this->json('serviceErrors',[]))
            ->map(fn(array $serviceErrors) => new ServiceError($serviceErrors));
    }


    public function firstAuthServiceError(): ?ServiceError
    {
        return $this->getAuthServiceErrors()->first();
    }

    /**
     * 获取信任电话号码
     */
    public function getTrustedPhoneNumber(): ?Phone
    {
        $data = $this->getJsonData()['direct']['twoSV']['phoneNumberVerification']['trustedPhoneNumber'] ?? [];

        return $data ? new Phone($data) : null;
    }

    public function getJsonData(): array
    {
        if ($this->jsonData === null) {
            $this->jsonData = $this->document->getJson() ?? [];
        }
        return $this->jsonData;
    }

    /**
     * 获取指定 ID 的信任电话号码
     */
    public function getTrustedPhoneNumberById(int $id): ?Phone
    {
        return $this->getTrustedPhoneNumbers()->first(fn(Phone $phone) => $phone->getId() === $id);
    }

    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->response->{$method}(...$parameters);
    }
}
