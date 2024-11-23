<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage;

use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;
use UpdateNameData;

class UpdateName extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        public UpdateNameData $dto,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/account/manage/name";
    }

    public function createDtoFromResponse(Response $response): UpdateNameData
    {
        return UpdateNameData::from($response->json());
    }

    protected function defaultBody(): array
    {
        return $this->dto->toArray();
    }
}
