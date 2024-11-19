<?php

namespace Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates;

use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\GameCenter\GameCenter;
use Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\Ids\Ids;
use Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\MobileMe\MobileMe;
use Spatie\LaravelData\Attributes\MapName;

class Delegate extends Data
{
    public function __construct(
        #[MapName('com.apple.mobileme')]
        public ?MobileMe $mobileMeService = null,
        #[MapName('com.apple.gamecenter')]
        public ?GameCenter $gameCenterService = null,
        #[MapName('com.apple.private.ids')]
        public ?Ids $idsService = null,
        public int $status = 0,
    ) {
    }
}
