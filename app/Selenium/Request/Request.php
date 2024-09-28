<?php

namespace App\Selenium\Request;

use App\Selenium\PendingRequest;
use App\Selenium\Trait\HasMiddleware;

abstract class Request
{
    use HasMethod;
    use HasMiddleware;

    abstract public function resolveEndpoint(): string;

    public function actions(PendingRequest $pendingRequest): ?PendingRequest
    {
        return null;
    }
}


