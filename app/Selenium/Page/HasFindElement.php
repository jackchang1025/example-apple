<?php

namespace App\Selenium\Page;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;

trait HasFindElement
{
    public function findElement(WebDriverBy $by): WebDriverElement
    {
        return $this->findRootElement()
            ->findElement($by);
    }

    public function findElements(WebDriverBy $by):array
    {
        return $this->findRootElement()
            ->findElements($by);
    }

    public function findElementByCssSelector(string $selector): WebDriverElement
    {
        return $this->findRootElement()
            ->findElement(WebDriverBy::cssSelector($selector));
    }

    public function findElementByXpath(string $selector): WebDriverElement
    {
        return $this->findRootElement()
            ->findElement(WebDriverBy::xpath($selector));
    }

    public function findElementById(string $selector): WebDriverElement
    {
        return $this->findRootElement()
            ->findElement(WebDriverBy::id($selector));
    }
}
