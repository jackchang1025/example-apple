<?php

namespace Modules\AppleClient\Service\DataConstruct\Icloud\LoginDelegates\Ids;

use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\Helpers\CustomSnakeCaseMapper;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\DataCollection;

#[MapName(CustomSnakeCaseMapper::class)]
class ServiceData extends Data
{

    public function __construct(
        public string $appleId,
        public string $profileId,
        #[DataCollectionOf(Handles::class)]
        public ?DataCollection $handles = null,
        public ?string $emailAddress = null,
        public ?string $authToken = null,
        public ?array $selfHandle = null,
        public ?InvitationContext $invitationContext = null,
        public ?string $realmUserId = null,
    ) {
    }
}
