<?php

namespace App\Selenium\Trait;

use Symfony\Component\Panther\Client;

trait HasClient
{
    abstract public function client(): Client;
}
