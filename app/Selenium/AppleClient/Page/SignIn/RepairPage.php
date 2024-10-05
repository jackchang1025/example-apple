<?php

namespace App\Selenium\AppleClient\Page\SignIn;

use App\Selenium\AppleClient\Page\IframePage;
use App\Selenium\Connector;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class RepairPage extends IframePage
{

    public function __construct(protected Connector $connector){

        parent::__construct($connector);

        $this->driver->switchTo()->frame($this->resolveFrameElement());
    }

    /**
     * @return void
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    public function repair(): void
    {
        try {

            $this->driver->wait()->until(
                WebDriverExpectedCondition::presenceOfElementLocated(
                    WebDriverBy::cssSelector('button.button.button-primary.last.nav-action.pull-right.weight-medium')
                )
            )->click();

        } catch (NoSuchElementException|TimeoutException $e) {

            $this->takeScreenshot('repair-page.png');

            throw $e;
        }
    }

    public function title():string
    {
        return 'Apple Account & Privacy';
    }

    public function getTitle(): string
    {
        return $this->findElement(WebDriverBy::cssSelector('h2.tk-manifesto text-centered'))->getText();
    }

    public function resolveFrameElement()
    {
        return $this->driver->wait()->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('iframe#repairFrame')
            )
        );
    }

}
