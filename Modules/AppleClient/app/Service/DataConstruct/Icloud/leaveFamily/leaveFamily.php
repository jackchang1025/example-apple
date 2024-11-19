<?php

namespace Modules\AppleClient\Service\DataConstruct\Icloud\leaveFamily;

use Modules\AppleClient\Service\DataConstruct\Data;
use Spatie\LaravelData\Attributes\MapName;

class leaveFamily extends Data
{
    public function __construct(
        #[MapName('status-message')]
        public string $statusMessage,
        public int $status,
        public ?string $title = null,
        public bool $isMemberOfFamily = false,
    ) {
    }
}
