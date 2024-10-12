<?php

namespace App\Apple\Trait\Response;

use App\Apple\DataConstruct\Phone;
use App\Apple\DataConstruct\ServiceError;
use Illuminate\Support\Collection;

trait HasPhoneNumbers
{
    public function getTrustedPhoneNumber(): ?Phone
    {
        $data = $this->authorizeSing()['direct']['twoSV']['phoneNumberVerification']['trustedPhoneNumber'] ?? [];

        return $data ? new Phone($data) : null;
    }

    /**
     * 获取所有信任的电话号码
     *
     * @return Collection
     */
    public function getTrustedPhoneNumbers(): Collection
    {
        return collect($this->authorizeSing()['direct']['twoSV']['phoneNumberVerification']['trustedPhoneNumbers'] ?? [])
            ->map(fn(array $phone) => new Phone($phone));
    }

    /**
     * 获取电话号码验证信息
     * @throws \JsonException
     */
    public function phoneNumberVerification(): ?array
    {
        return $this->json('phoneNumberVerification');
    }
}
