<?php

namespace App\Selenium\AppleClient;

use App\Selenium\Connector;
use Facebook\WebDriver\WebDriver;

class AppleConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return 'https://apple.com';
    }

    public function defaultScreenshotPath():string
    {
        return $this->config()
            ->get('screenshot_path',__DIR__);
    }

    public function webDriver(): WebDriver
    {
        return $this->client()->getWebDriver();
    }
}
