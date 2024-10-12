<?php

namespace App\Apple\Integrations\AppleId\Request\AccountManage;

use App\Apple\Integrations\Idmsa\Request\Request;
use Saloon\Enums\Method;

class PrivacyAccept extends Request
{
    protected Method $method = Method::OPTIONS;


    public function resolveEndpoint(): string
    {
        return '/account/manage/privacy/accept';
    }
    
    public function defaultHeaders(): array
    {
        return [
            'X-Apple-Widget-Key'    => $this->appleConfig()->getServiceKey(),
        ];
    }
}