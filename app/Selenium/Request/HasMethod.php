<?php

namespace App\Selenium\Request;

use LogicException;

trait HasMethod
{
    protected Method $method;

    /**
     * Get the method of the request.
     */
    public function getMethod(): Method
    {
        if (! isset($this->method)) {
            throw new LogicException('Your request is missing a HTTP method. You must add a method property like [protected Method $method = Method::GET]');
        }

        return $this->method;
    }
}
