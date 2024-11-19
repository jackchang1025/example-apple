<?php

namespace Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\GameCenter;

use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\Helpers\CustomSnakeCaseMapper;
use Spatie\LaravelData\Attributes\MapName;

#[MapName(CustomSnakeCaseMapper::class)]
class GameCenter extends Data
{
    public function __construct(
        public int $status,
        public ?string $message = null,
        public ?ServiceData $serviceData = null,
        public bool $accountExists = false,
    ) {
    }
}
