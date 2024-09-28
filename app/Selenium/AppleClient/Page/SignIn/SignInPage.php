<?php

namespace App\Selenium\AppleClient\Page\SignIn;

use App\Selenium\AppleClient\Page\IframePage;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class SignInPage extends IframePage
{
    public function defaultExceptionSelector(): WebDriverBy
    {
        return WebDriverBy::cssSelector('.error.pop-bottom.tk-subbody-headline p#errMsg');
    }

    public function defaultAlertInfoSelector():?WebDriverBy
    {
        return WebDriverBy::cssSelector('.idms-modal-dialog h2#alertInfo');
    }

    public function inputAccountName(string $accountName):RemoteWebElement
    {
        $accountNameField = $this->driver->wait()->until(
            WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::id('account_name_text_field'))
        );

        $accountNameField->click()->clear()->sendKeys($accountName);

        return $accountNameField;
    }

    public function inputPassword(string $password)
    {
        $passwordElement = $this->driver->wait()->until(
            WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::id('password_text_field'))
        );

        $passwordElement->click()
            ->clear()
            ->sendKeys($password);

        return $passwordElement;
    }

    /**
     * @return void
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @throws \App\Selenium\Exception\PageErrorException
     */
    public function signInAccountName(): void
    {
//        $signInButton = $this->driver->wait()->until(
//            WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::id('sign-in'))
//        );

        $signInButton = $this->driver->findElement(WebDriverBy::id('sign-in'));

        $signInButton->click();
        $this->throw();
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @throws \App\Selenium\Exception\PageErrorException
     */
    public function signInPassword(): SignInSelectPhonePage|SignInAuthPage
    {
        $signInButton = $this->driver->wait()->until(
            WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::id('sign-in'))
        );
        $signInButton->click();

        $this->throw();

        return $this->switchToSignInAuthPage();
    }

    /**
     * @return SignInSelectPhonePage|SignInAuthPage
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    protected function switchToSignInAuthPage(): SignInSelectPhonePage|SignInAuthPage
    {
        try {

            $page =  new SignInAuthPage($this->driver);

            if ($page->getTitle() === 'Two-Factor Authentication') {
                return $page;
            }

            return new SignInSelectPhonePage($this->driver);

        } catch (NoSuchElementException|TimeoutException $e) {

            return  new SignInSelectPhonePage($this->driver);
        }
    }
}
