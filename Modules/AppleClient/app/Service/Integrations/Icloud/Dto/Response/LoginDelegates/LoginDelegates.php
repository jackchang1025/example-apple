<?php


namespace Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\LoginDelegates;
use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\Helpers\CustomSnakeCaseMapper;
use Spatie\LaravelData\Attributes\MapName;

#[MapName(CustomSnakeCaseMapper::class)]
class LoginDelegates extends Data
{
    public function __construct(
        public int $status,
        public ?string $dsid = null,
        public ?Delegate $delegates = null,
        public ?string $statusMessage = null,
    ) {
    }
}
