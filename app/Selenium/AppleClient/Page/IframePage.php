<?php

namespace App\Selenium\AppleClient\Page;

use App\Selenium\Connector;
use App\Selenium\Page\Page;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

abstract class IframePage extends Page
{

    public function __construct(protected Connector $connector){

        parent::__construct($connector);

        $this->driver->switchTo()->frame($this->resolveRootFrameElement());
    }

    public function resolveRootFrameElement()
    {
        return $this->driver->wait()->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('iframe#aid-auth-widget-iFrame')
            )
        );
    }

    public function resolveRootElement(): WebDriverBy
    {
        return WebDriverBy::id('content');
    }
}
