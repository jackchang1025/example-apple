<?php

namespace App\Proxy;

use Illuminate\Config\Repository;

class Option extends Repository
{
    public static function make(array $items = []): self
    {
        return new self($items);
    }
}
