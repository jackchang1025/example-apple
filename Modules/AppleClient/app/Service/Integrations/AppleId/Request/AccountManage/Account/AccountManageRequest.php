<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\Account;

use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\AccountManager\AccountManager;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class AccountManageRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/account/manage';
    }

    public function createDtoFromResponse(Response $response): AccountManager
    {
        return AccountManager::from($response->json());
    }
}
