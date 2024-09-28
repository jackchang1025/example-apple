<?php

namespace App\Selenium\Trait;

use Facebook\WebDriver\WebDriver;

trait HasWebDriver
{
    abstract public function webDriver(): WebDriver;
}
