<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\LoginDelegates\Ids;

use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\Helpers\CustomSnakeCaseMapper;
use Spatie\LaravelData\Attributes\MapName;

#[MapName(CustomSnakeCaseMapper::class)]
class Ids extends Data
{
    public function __construct(
        public int $status,
        public ServiceData $serviceData,
        public ?Alert $alert = null,
        public ?string $message = null,
        public bool $accountExists = false
    ) {
    }
}
