<?php

namespace Modules\AppleClient\Service\DataConstruct\Icloud\FamilyInfo;

use Modules\AppleClient\Service\DataConstruct\Data;

class Family extends Data
{
    public function __construct(
        public string $familyId,
        public string $organizer,
        public string $etag,
        public array $transferRequests = [],
        public array $invitations = [],
        public array $members = [],
        public array $outgoingTransferRequests = [],
    ) {
    }
}
