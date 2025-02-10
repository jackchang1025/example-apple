<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Resources;

use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\AccountManager\AccountManager;
use Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage\Account\AccountManageRequest;
use Modules\AppleClient\Service\Integrations\BaseResource;

class AccountManagerResource extends BaseResource
{
    public function account(): AccountManager
    {
        return $this->connector->send(new AccountManageRequest())->dto();
    }
}
