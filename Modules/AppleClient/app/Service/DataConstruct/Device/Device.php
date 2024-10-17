<?php

namespace Modules\AppleClient\Service\DataConstruct\Device;

use App\Models\Devices;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;

class Device extends Data
{
    public function __construct(
        #[MapName('id')]
        public string $deviceId,
        public ?string $name,
        public ?string $deviceClass,
        public ?string $qualifiedDeviceClass,
        public ?string $modelName,
        public ?string $os,
        public ?string $osVersion,
        public bool $supportsVerificationCodes,
        public bool $currentDevice,
        public bool $unsupported,
        public bool $hasApplePayCards,
        public bool $hasActiveSurfAccount,
        public bool $removalPending,
        public ?string $listImageLocation,
        public ?string $listImageLocation2x,
        public ?string $listImageLocation3x,
        public ?string $infoboxImageLocation,
        public ?string $infoboxImageLocation2x,
        public ?string $infoboxImageLocation3x,
        public ?string $deviceDetailUri,
        public ?string $deviceDetailHttpMethod,
    ) {
    }

    public function updateOrCreate(int $accountId): \Illuminate\Database\Eloquent\Model|Devices
    {
        return Devices::updateOrCreate(
            [
                'account_id' => $accountId,
                'device_id'  => $this->deviceId,
            ],
            $this->toArray()
        );
    }
}
