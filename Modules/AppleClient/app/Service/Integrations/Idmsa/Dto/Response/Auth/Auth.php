<?php

namespace Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\Auth;

use Illuminate\Support\Str;
use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\DataConstruct\PhoneNumber;
use Modules\AppleClient\Service\Exception\MaxRetryAttemptsException;
use Modules\AppleClient\Service\Exception\PhoneNotFoundException;
use Modules\AppleClient\Service\Response\Response;
use Spatie\LaravelData\DataCollection;

class Auth extends Data
{
    /**
     * @param Direct $direct 直接相关的数据
     * @param Additional $additional 额外的数据
     */
    public function __construct(
        public Direct $direct,
        public Additional $additional
    ) {
    }

    public function getTrustedPhoneNumbers(): DataCollection
    {
        return $this->direct->twoSV->phoneNumberVerification->trustedPhoneNumbers;
    }

    public function filterTrustedPhone(string $phone): DataCollection
    {
        return $this->getTrustedPhoneNumbers()->filter(
            fn(PhoneNumber $trustedPhone) => Str::contains($phone, $trustedPhone->lastTwoDigits)
        );
    }

    public function filterTrustedPhoneById(int $id): ?PhoneNumber
    {
        return collect($this->getTrustedPhoneNumbers()->all())->first(fn($phone) => $id === $phone->id);
    }

    public function getTrustedPhoneNumber(): PhoneNumber
    {
        return $this->direct->twoSV->phoneNumberVerification->trustedPhoneNumber;
    }

    public function hasTrustedDevices(): bool
    {
        return $this->direct->hasTrustedDevices;
    }

    public static function fromResponse(Response $response): static
    {
        return self::from($response->authorizeSing());
    }
}
