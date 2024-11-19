<?php

namespace Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\Ids;

use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\Helpers\CustomSnakeCaseMapper;
use Spatie\LaravelData\Attributes\MapName;

#[MapName(CustomSnakeCaseMapper::class)]
class Handles extends Data
{
    public function __construct(
        public int $status,
        public bool $isUserVisible,
        public string $uri
    ) {
    }
}
