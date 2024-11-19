<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Request;

use Modules\AppleClient\Service\DataConstruct\Icloud\ITunesAccountPaymentInfo\ITunesAccountPaymentInfo;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class GetITunesAccountPaymentInfoRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        public readonly string $organizerDSID,
        public readonly string $userAction = "ADDING_FAMILY_MEMBER",
        public readonly bool $sendSMS = true,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/setup/mac/family/getiTunesAccountPaymentInfo';
    }

    public function defaultBody(): array
    {
        return [
            "organizerDSID" => $this->organizerDSID,
            "userAction"    => $this->userAction,
            "sendSMS"       => $this->sendSMS,
        ];
    }

    public function createDtoFromResponse(Response $response): ITunesAccountPaymentInfo
    {
        return ITunesAccountPaymentInfo::fromResponse($response);
    }
}
