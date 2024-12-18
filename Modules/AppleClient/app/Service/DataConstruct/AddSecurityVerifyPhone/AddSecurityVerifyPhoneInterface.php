<?php

namespace Modules\AppleClient\Service\DataConstruct\AddSecurityVerifyPhone;

interface AddSecurityVerifyPhoneInterface
{
    public function getCountryCode(): string;

    public function getPhoneNumber(): string;

    public function getCountryDialCode(): string;

    public function getPhoneAddress(): string;
}
