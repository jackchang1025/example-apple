<?php

namespace App\Selenium\AppleClient\Page\SignIn;

use App\Selenium\AppleClient\Exception\AccountException;
use App\Selenium\AppleClient\Page\IframePage;
use App\Selenium\Exception\PageException;
use App\Selenium\Page\Page;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class SignInPage extends IframePage
{

    public function title():string
    {
        return 'Apple Account';
    }

    public function getTitle(): string
    {
        return $this->findElement(WebDriverBy::cssSelector('h1.si-container-title.tk-callout'))->getText();
    }

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

        $accountNameField->click()
            ->clear()
            ->sendKeys($accountName);

        return $accountNameField;
    }

    public function inputPassword(string $password)
    {
        $passwordElement = $this->driver->findElement(WebDriverBy::id('password_text_field'));

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
     * @throws \App\Selenium\Exception\PageErrorException|PageException
     */
    public function signInPassword(): Page
    {

        $signInButton = $this->driver->wait()->until(
            WebDriverExpectedCondition::elementToBeClickable(WebDriverBy::id('sign-in'))
        );

        try {

            $signInButton->click();

            $this->throwif(function (Page $page,PageException $exception){

                return $exception->getMessage() !== 'Too many verification codes have been sent. Enter the last code you received or try again later.';
            });

            return $this->switchToSignInAuthPage();
        } catch (PageException|NoSuchElementException|TimeoutException $e) {

            $this->takeScreenshot("sign.png");

            throw $e;
        }
    }


    public function defaultException(Page $page,string $message): PageException
    {
        return new AccountException($page, $message);
    }


    protected function switchToSignInAuthPage(): Page
    {
        try {

            return $this->attemptSwitchToPage(new TwoFactorAuthenticationPage($this->connector));

        } catch (NoSuchElementException|TimeoutException $e) {
            // Ignore and attempt next page
        }

        try {

            return $this->attemptSwitchToPage(new SignInSelectPhonePage($this->connector));

        } catch (NoSuchElementException|TimeoutException $e) {
            // Ignore since we'll rethrow at the end
        }

        throw new PageException($this, 'Can not switch to sign in auth page');
    }

    private function attemptSwitchToPage(Page $page): Page
    {
        if ($page->isCurrentTitle()) {
            return $page;
        }

        throw new NoSuchElementException("The page with title '{$page->title()}' is not currently displayed.");
    }


}
