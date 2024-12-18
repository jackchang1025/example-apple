<?php

namespace Modules\AppleClient\Events\Authenticated;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\AppleClient\Service\Apple;

class AuthenticatedEvent
{
    use Dispatchable;

    public function __construct(public Apple $apple)
    {
    }
}