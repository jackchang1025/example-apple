<?php

namespace App\Selenium\AppleClient\Elements;

use Illuminate\Support\Collection;

class PhoneList extends Collection
{
    /**
     * @param string $key
     * @return Phone|null
     *
     */
    public function hasMatch(string $key):?Phone
    {
        return $this->first(fn(Phone $phone) => $phone->getPhoneNumberService()->isMatch($key));
    }

}
