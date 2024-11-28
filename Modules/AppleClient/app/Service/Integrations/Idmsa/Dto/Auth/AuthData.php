<?php

namespace Modules\AppleClient\Service\Integrations\Idmsa\Dto\Auth;

use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\Response\Response;

class AuthData extends Data
{
    /**
     * @param DirectData $direct 直接相关的数据
     * @param AdditionalData $additional 额外的数据
     */
    public function __construct(
        public DirectData $direct,
        public AdditionalData $additional
    ) {
    }

    public function getTrustedPhoneNumbers(): \Spatie\LaravelData\DataCollection
    {
        return $this->direct->twoSV->phoneNumberVerification->trustedPhoneNumbers;
    }

    public function getTrustedPhoneNumber(): \Modules\AppleClient\Service\DataConstruct\PhoneNumber
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
