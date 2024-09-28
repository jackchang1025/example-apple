<?php

namespace App\Selenium;

use App\Selenium\Trait\HasConfig;
use App\Selenium\Trait\HasMiddleware;
use App\Selenium\Trait\HasSender;
use App\Selenium\Trait\HasSession;
use App\Selenium\Trait\Macroable;

/**
 * @mixin \Symfony\Component\Panther\Client $client
 */
abstract class Connector
{
    use HasSender;
    use SendsRequests;
    use HasMiddleware;
    use HasConfig;
    use Macroable {
        __call as __callMacro;
    }
    use HasSession;

    public function __call($method, $parameters)
    {
        try {

            return $this->__callMacro($method, $parameters);

        } catch (\BadMethodCallException $e) {

            return $this->client()->{$method}(...$parameters);
        }
    }
}
