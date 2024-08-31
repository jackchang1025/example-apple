<?php

namespace App\Apple\Trait\Response;

use App\Apple\DataConstruct\ServiceError;
use Illuminate\Support\Collection;

trait HasServiceError
{

    /**
     * @throws \JsonException
     */
    public function service_errors(): Collection
    {
        return collect($this->json('service_errors',[]))
            ->map(fn(array $serviceErrors) => new ServiceError($serviceErrors));
    }

    /**
     * @throws \JsonException
     */
    public function service_errors_first(): ?ServiceError
    {
        return $this->service_errors()->first();
    }

    /**
     * @throws \JsonException
     */
    public function getServiceErrors(): Collection
    {
        return collect($this->json('serviceErrors',[]))
            ->map(fn(array $serviceErrors) => new ServiceError($serviceErrors));
    }

    /**
     * @throws \JsonException
     */
    public function getFirstError(): ?ServiceError
    {
        return $this->getServiceErrors()->first();
    }

    public function getAuthServiceErrors(): Collection
    {
        return collect($this->authorizeSing()['direct']['twoSV']['phoneNumberVerification']['serviceErrors'] ?? [])
            ->map(fn(array $serviceErrors) => new ServiceError($serviceErrors));
    }

    public function firstAuthServiceError(): ?ServiceError
    {
        return $this->getAuthServiceErrors()->first();
    }

    public function hasError():bool
    {
        return $this->json('hasError', false);
    }

    public function validationErrors(): Collection
    {
        return collect($this->json('validationErrors',[]))
            ->map(fn(array $serviceErrors) => new \App\Apple\Service\DataConstruct\ServiceError($serviceErrors));
    }

    public function validationErrorsFirst(): ?ServiceError
    {
        return $this->validationErrors()->first();
    }
}
