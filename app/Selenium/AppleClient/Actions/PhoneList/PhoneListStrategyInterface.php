<?php

namespace App\Selenium\AppleClient\Actions\PhoneList;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;

interface PhoneListStrategyInterface
{
    public function containerSelector():WebDriverBy;
    public function itemSelector():WebDriverBy;
    public function phoneSelector():WebDriverBy;
    public function keyGenerator(int $index,WebDriverElement $phoneElement):int|string;
}
