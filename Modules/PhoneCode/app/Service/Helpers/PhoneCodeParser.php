<?php

namespace Modules\PhoneCode\Service\Helpers;


use Modules\PhoneCode\Service\PhoneCodeParserInterface;

class PhoneCodeParser implements PhoneCodeParserInterface
{
    public function parse(string $str): ?string
    {
        if (preg_match('/\b\d{6}\b/', $str, $matches)) {
            return $matches[0];
        }

        return null;
    }
}
