<?php

namespace Modules\AppleClient\Service\DataConstruct\Device;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class Devices extends Data
{

    public function __construct(

        #[DataCollectionOf(Device::class)]
        public DataCollection $devices,
        public string $hsa2SignedInDevicesLink,
        public bool $suppressChangePasswordLink,
    ) {
    }
}
