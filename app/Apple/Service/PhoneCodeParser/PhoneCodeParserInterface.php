<?php

namespace App\Apple\Service\PhoneCodeParser;

interface PhoneCodeParserInterface
{
    public function parse(string $body): ?string;
}
