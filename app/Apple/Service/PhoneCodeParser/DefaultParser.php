<?php

namespace App\Apple\Service\PhoneCodeParser;

use App\Apple\Service\Common;

class DefaultParser implements PhoneCodeParserInterface
{
    public function parse(string $body): ?string
    {
        if (preg_match('/\b\d{6}\b/', $body, $matches)) {
            return $matches[0];
        }

        return null;
    }

    //
}
