<?php

namespace Modules\PhoneCode\Service;

interface PhoneCodeParserInterface
{
    public function parse(string $str):?string;
}
