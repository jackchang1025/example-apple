<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\LoginDelegates\GameCenter;
use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\Helpers\CustomSnakeCaseMapper;
use Spatie\LaravelData\Attributes\MapName;

#[MapName(CustomSnakeCaseMapper::class)]
class GameCenter extends Data
{
    public function __construct(
        public int $status,
        public ?string $message = null,
        public ?Service $serviceData = null,
        public bool $accountExists = false,
    ) {
    }
}
