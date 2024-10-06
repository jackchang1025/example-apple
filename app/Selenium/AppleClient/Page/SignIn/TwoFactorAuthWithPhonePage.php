<?php

namespace App\Selenium\AppleClient\Page\SignIn;

use App\Selenium\Exception\PageException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;

class TwoFactorAuthWithPhonePage extends TwoFactorAuthenticationPage
{

    /**
     * @return void
     * @throws PageException
     */
    public function showOptionsPopoverContainer(): void
    {
        if (!$this->otherOptionsPopoverContainerIsVisible()){

            if ($element = $this->getOtherOptionsPopoverContainerButton()){
                $element->click();
                return;
            }

            if ($element = $this->getDidntVerificationCodeButton()){
                $element->click();
                return;
            }

            throw new PageException($this,'Unable to find options popover container button');
        }
    }

    /**
     * @return WebDriverElement|null
     */
    public function getOtherOptionsPopoverContainerButton():?WebDriverElement
    {
        try {
            return $this->driver->findElement(
                WebDriverBy::xpath("//button[.//span[contains(text(), 'Other options')]]")
            );
        } catch (NoSuchElementException $e) {
            return null;
        }
    }

    /**
     * @return WebDriverElement|null
     * @throws \Exception
     */
    public function getUseDifferentPhoneNumberButton():?WebDriverElement
    {
        try {

            return $this->driver->wait(10)->until(
                WebDriverExpectedCondition::presenceOfElementLocated(
                    WebDriverBy::xpath("//button[.//span[contains(text(), 'Use different phone number')]]")
                )
            );

        } catch (NoSuchElementException|TimeoutException) {
            return null;
        }
    }
    public function useDifferentPhoneNumber(): SignInSelectPhonePage
    {
        $this->getUseDifferentPhoneNumberButton()?->click();

        return new SignInSelectPhonePage($this->connector);
    }

    public function callMe(): void
    {
        $this->showOptionsPopoverContainer();

        $this->getCallMeButton()
            ->click();
    }

    public function getCallMeButton(): WebDriverElement
    {
        return $this->driver->findElement(
            WebDriverBy::xpath("//button[.//div[contains(text(), 'Call Me')]]")
        );
    }
}
