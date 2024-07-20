<?php

namespace App\Apple\Service\User;

class Config
{
    public function __construct(
        protected string $apiUrl = '',
        protected string $serviceKey = '',
        protected string $serviceUrl = '',
        protected string $environment = '',
        protected int $timeOutInterval = 0,
        protected int $moduleTimeOutInSeconds = 0,
        protected ?string $XAppleIDSessionId = null,
        protected array $pageFeatures = [],
        protected array $signoutUrls = [],
        protected array $phoneInfo = []
    ) {

    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public static function fromArray(array $data): self
    {
        return new self(...$data);
    }

    public function setPhoneInfo(array $phoneInfo): void
    {
        $this->phoneInfo = $phoneInfo;
    }

    public function getPhoneInfo(): array
    {
        return $this->phoneInfo;
    }


    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function getServiceKey(): string
    {
        return $this->serviceKey;
    }

    public function getServiceUrl(): string
    {
        return $this->serviceUrl;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function getTimeOutInterval(): int
    {
        return $this->timeOutInterval;
    }

    public function getModuleTimeOutInSeconds(): int
    {
        return $this->moduleTimeOutInSeconds;
    }

    public function getPageFeatures(): array
    {
        return $this->pageFeatures;
    }

    public function getSignoutUrls(): array
    {
        return $this->signoutUrls;
    }

    public function getXAppleIDSessionId(): string
    {
        return $this->XAppleIDSessionId;
    }

    public function setXAppleIDSessionId(string $XAppleIDSessionId): void
    {
        $this->XAppleIDSessionId = $XAppleIDSessionId;
    }
}
