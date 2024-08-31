<?php

namespace App\Apple\Integrations\Idmsa\Response;

use App\Apple\Trait\Response\HasAuth;
use App\Apple\Trait\Response\HasPhoneNumbers;
use Saloon\Http\Response;

class AuthResponse extends Response
{
    use HasAuth;
    use HasPhoneNumbers;

}