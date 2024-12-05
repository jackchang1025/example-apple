<?php
namespace Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\LoginDelegates\MobileMe;
use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\Helpers\CustomSnakeCaseMapper;
use Spatie\LaravelData\Attributes\MapName;

#[MapName(CustomSnakeCaseMapper::class)]
class MobileMe extends Data
{
    public function __construct(
        public int $status,
        public ?string $statusMessage = null,
        public ?string $statusError = null,
        public ?ServiceData $serviceData = null
    ) {
    }
}
