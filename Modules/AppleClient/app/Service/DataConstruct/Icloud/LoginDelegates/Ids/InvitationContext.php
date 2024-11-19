<?php

namespace Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\Ids;

use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\Helpers\CustomSnakeCaseMapper;
use Spatie\LaravelData\Attributes\MapName;

#[MapName(CustomSnakeCaseMapper::class)]
class InvitationContext extends Data
{
    public function __construct(
        public array $extra,
        public string $basePhoneNumber,
        public string $regionId
    ) {
    }
}
