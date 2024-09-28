<?php

namespace App\Selenium\AppleClient\Actions\PhoneList;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;

class DeviceContainerStrategy implements PhoneListStrategyInterface
{
    public function containerSelector(): WebDriverBy
    {
        return WebDriverBy::cssSelector('.container.si-field-container.si-device-container');
    }

    public function itemSelector(): WebDriverBy
    {
        return WebDriverBy::tagName('li');
    }

    public function phoneSelector(): WebDriverBy
    {
        return WebDriverBy::cssSelector('.si-device-name.force-ltr');
    }

    public function keyGenerator(int $index,WebDriverElement $phoneElement): int|string
    {
        return $index;
    }
}
