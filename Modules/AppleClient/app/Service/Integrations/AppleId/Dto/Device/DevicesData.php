<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto\Device;

use Modules\AppleClient\Service\DataConstruct\Data;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

class DevicesData extends Data
{

    public function __construct(

        #[DataCollectionOf(DeviceData::class)]
        public DataCollection $devices,
        public string $hsa2SignedInDevicesLink,
        public bool $suppressChangePasswordLink,
    ) {
    }
}
