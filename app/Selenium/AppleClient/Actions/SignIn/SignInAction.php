<?php

namespace App\Selenium\AppleClient\Actions\SignIn;

use App\Selenium\Actions\Actions;
use App\Selenium\AppleClient\Exception\AccountException;
use App\Selenium\AppleClient\Exception\AccountLockoutException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class SignInAction extends Actions
{

    public function perform(): void
    {
        $signInButton = $this->page->webDriver()->wait()->until(
            WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::id('sign-in'))
        );
        $signInButton->click();

        sleep(10);

        $this->page->webDriver()->wait(5)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('.idms-modal-dialog h2#alertInfo')
            )
        );


//        $this->checkForErrors();
    }

    /**
     * @return void
     * @throws AccountException
     * @throws AccountLockoutException
     */
    protected function checkForErrors(): void
    {
        if ($exception = $this->page->getAlertInfoElement()?->getText()){
            throw new AccountLockoutException($exception);
        }

        if ($exception = $this->page->getExceptionElement()?->getText()){
            throw new AccountException($exception);
        }
    }
}
