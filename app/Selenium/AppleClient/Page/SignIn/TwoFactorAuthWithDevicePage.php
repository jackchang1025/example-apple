<?php

namespace App\Selenium\AppleClient\Page\SignIn;

use App\Selenium\Exception\PageException;
use App\Selenium\Page\Page;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;

class TwoFactorAuthWithDevicePage extends TwoFactorAuthenticationPage
{

    /**
     * @return SignInSelectPhonePage
     * @throws NoSuchElementException|PageException
     */
    public function usePhoneNumber(): Page
    {
        $this->showOptionsPopoverContainer();

        $this->getUsePhoneNumberButton()
            ->click();

        return $this->switchToSignInAuthPage();
    }

    /**
     * 获取 "Use Phone Number" 按钮元素
     */
    public function getUsePhoneNumberButton(): WebDriverElement
    {
        return $this->driver->findElement(
            WebDriverBy::xpath("//button[.//div[contains(text(), 'Use Phone Number')]]")
        );
    }

}
