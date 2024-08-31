<?php

namespace App\Apple\Integrations\Idmsa\Response;

use App\Apple\Trait\Response\HasServiceError;
use Saloon\Http\Response;

class VerifyTrustedDeviceSecurityCodeResponse extends Response
{
    use HasServiceError;
}
