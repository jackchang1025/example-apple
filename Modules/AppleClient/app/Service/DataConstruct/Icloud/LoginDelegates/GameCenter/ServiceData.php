<?php

namespace Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\GameCenter;


use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\Helpers\CustomSnakeCaseMapper;
use Spatie\LaravelData\Attributes\MapName;

#[MapName(CustomSnakeCaseMapper::class)]
class ServiceData extends Data
{
    public function __construct(
        public string $allowContactLookup,
        #[MapName('lastName')]
        public string $lastName,
        public int $lastUpdated,
        public string $alias,
        public string $authToken,
        public string $playerId,
        public string $dsid,
        #[MapName('firstName')]
        public string $firstName,
        public string $env,
        public string $appleId,
    ) {

    }
}
