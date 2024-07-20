<?php

namespace App\Apple\Service\PhoneCodeParser;

use App\Apple\Service\Common;

class PlatformBParser implements PhoneCodeParserInterface
{
    public function parse(string $body): ?string
    {
        $body = trim($body, '"');
        $body = stripslashes($body);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return empty($data['data']) ? null : Common::extractSixDigitNumber($data['data']);
    }
}
