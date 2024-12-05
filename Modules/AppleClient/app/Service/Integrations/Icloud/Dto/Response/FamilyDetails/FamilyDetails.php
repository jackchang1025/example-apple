<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\FamilyDetails;
use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\Helpers\CustomSnakeCaseMapper;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\DataCollection;

#[MapName(CustomSnakeCaseMapper::class)]
class FamilyDetails extends Data
{
    public function __construct(
        public string $statusMessage,
        public int $dsid,
        public bool $isMemberOfFamily,
        public int $status,
        #[DataCollectionOf(PendingMember::class)]
        public ?DataCollection $pendingMembers = null,
        #[DataCollectionOf(FamilyMember::class)]
        public ?DataCollection $familyMembers = null,
    ) {
    }
}
