<?php

namespace Modules\AppleClient\Service\Helpers;

use Illuminate\Support\Str;
use Spatie\LaravelData\Mappers\NameMapper;

class CustomSnakeCaseMapper implements NameMapper
{

    public function map(int|string $name): string|int
    {
        return Str::snake($name, '-');
    }
}
