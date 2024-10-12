<?php

namespace App\Apple\Help;

class PhoneCodeParser
{
    public static function parse(string $body): ?string
    {
        if (preg_match('/\b\d{6}\b/', $body, $matches)) {
            return $matches[0];
        }

        return null;
    }
}
