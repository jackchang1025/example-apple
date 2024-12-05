<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Dto\Request\AccountLogin;

use Spatie\LaravelData\Data;

class AccountLogin extends Data
{
    public function __construct(
        public  string $clientBuildNumber,
        public  string $clientMasteringNumber,
        public  string $clientId,
        public  string $dsWebAuthToken,
        public  string $accountCountryCode = 'CHN',
        public  bool $extended_login = false
    )
    {
    }
}
