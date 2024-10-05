<?php

namespace App\Selenium\AppleClient\Page;

use App\Selenium\Connector;
use App\Selenium\Exception\ElementNotVisibleException;
use App\Selenium\Page\Page;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

abstract class IframePage extends Page
{

    public function __construct(protected Connector $connector){

        parent::__construct($connector);

        $this->driver->switchTo()->frame($this->resolveRootFrameElement());
    }
    protected function ensureAsideIsVisible(): void
    {
        if ($this->isVisible()) {
            throw new ElementNotVisibleException("{$this->getTitle()} modal is hidden");
        }
    }


    public function isVisible(): bool
    {
        try {

            $this->driver->switchTo()->frame($this->resolveRootFrameElement());

            $this->driver->wait()->until(
                WebDriverExpectedCondition::presenceOfElementLocated($this->resolveRootElement())
            );

            return false;
        } catch (NoSuchElementException|TimeoutException $e) {
            return true;
        }
    }

    public function resolveRootFrameElement()
    {
        return $this->driver->wait()->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('iframe#aid-auth-widget-iFrame')
            )
        );
    }

//    public function resolveRootElement(): WebDriverBy
//    {
//        return WebDriverBy::cssSelector('iframe#aid-auth-widget-iFrame');
//    }

    public function resolveRootElement(): WebDriverBy
    {
        return WebDriverBy::id('content');
    }
}
