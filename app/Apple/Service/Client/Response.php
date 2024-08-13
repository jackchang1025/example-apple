<?php

namespace App\Apple\Service\Client;

use App\Apple\Service\DataConstruct\Phone;
use App\Apple\Service\DataConstruct\ServiceError;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;

class Response
{
    private ?Collection $trustedPhoneNumbers = null;
    private ?Collection $serviceErrors = null;

    /**
     * @param ResponseInterface $response HTTP 响应对象
     * @param int $status HTTP 状态码
     * @param array $data 响应数据
     */
    public function __construct(
        protected ResponseInterface $response,
        protected int $status,
        protected array $data
    ) {
    }

    /**
     * 获取原始 HTTP 响应对象
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * 获取 HTTP 状态码
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * 获取所有服务错误
     */
    public function getError(): array
    {
        return $this->data['serviceErrors'] ?? [];
    }

    public function hasTrustedDevices():bool
    {
        return $this->data['hasTrustedDevices'] ?? false;
    }

    /**
     * 获取第一个服务错误
     */
    public function getFirstError(): ?array
    {
        return $this->getError()[0] ?? null;
    }

    /**
     * 获取第一个服务错误的消息
     */
    public function getFirstErrorMessage(): ?string
    {
        return $this->getFirstError()['message'] ?? null;
    }

    /**
     * 获取指定键的数据，如果键不存在则返回默认值
     */
    public function getData(?string $key = null, mixed $default = null): mixed
    {
        if ($key !== null) {
            return $this->data[$key] ?? $default;
        }

        return $this->data;
    }

    /**
     * 获取电话号码信息
     */
    public function getPhoneNumber(): ?Phone
    {
        $data = $this->getData('phoneNumber');

        return $data ? new Phone($data) : null;
    }

    /**
     * 获取安全码信息
     */
    public function getSecurityCode(): array
    {
        return $this->getData('securityCode', []);
    }

    /**
     * 检查是否为 HSA2 账户
     */
    public function isHsa2Account(): bool
    {
        return $this->getData('hsa2Account', false);
    }

    /**
     * 检查是否为受限账户
     */
    public function isRestrictedAccount(): bool
    {
        return $this->getData('restrictedAccount', false);
    }

    /**
     * 检查是否支持恢复
     */
    public function supportsRecovery(): bool
    {
        return $this->getData('supportsRecovery', false);
    }

    /**
     * 获取电话号码验证信息
     */
    public function phoneNumberVerification(): ?array
    {
        return $this->getData('phoneNumberVerification');
    }

    /**
     * 获取恢复 URL
     */
    public function getRecoveryUrl(): ?string
    {
        return $this->getData('recoveryUrl');
    }

    /**
     * 获取无法使用电话号码的 URL
     */
    public function getCantUsePhoneNumberUrl(): ?string
    {
        return $this->getData('cantUsePhoneNumberUrl');
    }

    /**
     * 检查是否没有信任的设备
     */
    public function hasNoTrustedDevices(): bool
    {
        return $this->getData('noTrustedDevices', false);
    }

    /**
     * 获取关于双重认证的 URL
     */
    public function getAboutTwoFactorAuthenticationUrl(): ?string
    {
        return $this->getData('aboutTwoFactorAuthenticationUrl');
    }

    /**
     * 检查是否为托管账户
     */
    public function isManagedAccount(): bool
    {
        return $this->getData('managedAccount', false);
    }

    /**
     * 获取所有信任的电话号码
     *
     * @return Collection
     */
    public function getTrustedPhoneNumbers(): Collection
    {
        if ($this->trustedPhoneNumbers === null) {
            $this->trustedPhoneNumbers = collect($this->getData()['twoSV']['phoneNumberVerification']['trustedPhoneNumbers'] ?? [])
                ->map(fn(array $phoneData) => new Phone($phoneData));
        }

        return $this->trustedPhoneNumbers;
    }

    public function getAuthServiceErrors(): Collection
    {
        if ($this->serviceErrors === null) {
            $this->serviceErrors = collect($this->getData()['twoSV']['phoneNumberVerification']['serviceErrors'] ?? [])
                ->map(fn(array $serviceErrors) => new ServiceError($serviceErrors));
        }

        return $this->serviceErrors;
    }

    public function getServiceErrors(): ?Collection
    {
        if ($this->serviceErrors === null) {
            $this->serviceErrors = collect($this->getData('serviceErrors',[]))
                ->map(fn(array $serviceErrors) => new ServiceError($serviceErrors));
        }

        return $this->serviceErrors;
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
        $data = $this->getData()['twoSV']['phoneNumberVerification']['trustedPhoneNumber'] ?? [];

        return $data ? new Phone($data) : null;
    }

    /**
     * 获取指定 ID 的信任电话号码
     */
    public function getTrustedPhoneNumberById(int $id): ?Phone
    {
        return $this->getTrustedPhoneNumbers()->first(fn(Phone $phone) => $phone->getId() === $id);
    }
}
