<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\FamilyDetails;

use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\Helpers\CustomSnakeCaseMapper;
use Spatie\LaravelData\Attributes\MapName;

#[MapName(CustomSnakeCaseMapper::class)]
class PendingMember extends Data
{

    public function __construct(
        public string $memberInviteEmail,
        public string $memberStatus,
        public string $memberDisplayLabel
    ) {
    }
}
