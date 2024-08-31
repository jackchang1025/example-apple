<?php

namespace App\Apple\Integrations;

use App\Apple\Trait\Response\HasAuth;
use App\Apple\Trait\Response\HasPhoneNumbers;
use App\Apple\Trait\Response\HasServiceError;

class Response extends \Saloon\Http\Response
{
    use HasServiceError;
    use HasAuth;
    use HasPhoneNumbers;
}