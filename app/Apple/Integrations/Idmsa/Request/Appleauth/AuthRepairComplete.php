<?php

namespace App\Apple\Integrations\Idmsa\Request\Appleauth;

use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;

class AuthRepairComplete extends Request
{
    protected Method $method = Method::POST;

    public function resolveEndpoint(): string
    {
        return '/appleauth/auth/repair/complete';
    }

    public function persistentHeaders(): array
    {
        return [
            'X-Apple-ID-Session-Id' => function (PendingRequest $pendingRequest) {

                /**
                 * @var \App\Apple\Integrations\AppleConnector $connector
                 */
                $connector = $pendingRequest->getConnector();

                return $connector->getCookieJar()->getCookieByName('aasp')?->getValue();
            },
            'X-Apple-Repair-Session-Token' ,
        ];
    }
}
