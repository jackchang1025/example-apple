<?php

namespace App\Selenium\AppleClient\Page\SignIn;

use App\Apple\Service\Exception\VerificationCodeIncorrect;
use App\Selenium\AppleClient\Actions\InputTrustedCodeAction;
use App\Selenium\AppleClient\Page\AccountManage\AccountManagePage;
use App\Selenium\AppleClient\Page\IframePage;
use App\Selenium\AppleClient\SwitchToSignInAuthPage;
use App\Selenium\Exception\PageException;
use App\Selenium\Page\Page;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;


class TwoFactorAuthenticationPage extends IframePage
{
    use SwitchToSignInAuthPage;

    public function title(): string
    {
        return 'Two-Factor Authentication';
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isVerifyPhone(): bool
    {
        try {

            return (bool) $this->driver->wait(5)->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('#stepEl .verify-phone'))
            );

        } catch (NoSuchElementException|TimeoutException) {

            return false;
        }
    }

    public function otherOptionsPopoverContainerIsVisible(): ?bool
    {
        try {

            return (bool) $this->driver->findElement(WebDriverBy::cssSelector('.other-options-popover-container'));

        } catch (NoSuchElementException) {

            return false;
        }
    }

    /**
     * @return void
     * @throws NoSuchElementException
     */
    public function resendCode(): void
    {
        $this->showOptionsPopoverContainer();

        $this->getResendCodeButton()
            ->click();
    }

    /**
     * @return void
     */
    public function showOptionsPopoverContainer(): void
    {
        if (!$this->otherOptionsPopoverContainerIsVisible()){

            $this->getDidntVerificationCodeButton()
                ?->click();
        }
    }

    /**
     * @return WebDriverElement|null
     */
    public function getDidntVerificationCodeButton():?WebDriverElement
    {
        try {

            return $this->driver->findElement(WebDriverBy::xpath("//button[.//span[contains(text(), 'Didn’t get a verification code?')]]"));

        } catch (NoSuchElementException $e) {
            return null;
        }
    }

    /**
     * @return WebDriverElement
     * @throws NoSuchElementException
     */
    public function getResendCodeButton(): WebDriverElement
    {
        return $this->driver->findElement(
            WebDriverBy::xpath("//button[.//div[contains(text(), 'Resend Code')]]")
        );
    }



    /**
     * 获取 "More Options..." 按钮元素
     */
    public function getMoreOptionsButton(): WebDriverElement
    {
        return $this->driver->findElement(
            WebDriverBy::xpath("//button[.//div[contains(text(), 'More Options...')]]")
        );
    }



    /**
     * @return bool
     * @throws \Exception
     */
    public function isVerifyDevice(): bool
    {
        try {

            return (bool) $this->driver->wait(5)->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('#stepEl .verify-device'))
            );

        } catch (NoSuchElementException|TimeoutException $e) {

            return false;
        }
    }

    /**
     * @param string $code
     * @return AccountManagePage
     * @throws PageException
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    public function inputTrustedCode(string $code):AccountManagePage
    {

        $InputTrustedCodeAction = new InputTrustedCodeAction(
            page: $this,
            locator: WebDriverBy::cssSelector('.form-security-code-input'),
            code: $code
        );

        $InputTrustedCodeAction->perform();

        $this->throw();

        //判断是否隐私授权页面
        $page = new RepairPage($this->connector);

        if ($page->getResolveFrameElement()) {
            $page->repair();
        }

        return new AccountManagePage($this->connector);
    }

    public function defaultException(Page $page,string $message): PageException
    {
        return new VerificationCodeIncorrect($page, $message);
    }

    public function getTitle(): string
    {
        return $this->findElement(WebDriverBy::cssSelector('h1.tk-callout'))->getText();
    }

    public function defaultExceptionSelector(): WebDriverBy
    {
        return WebDriverBy::cssSelector('.form-message-wrapper span.form-message');
    }
}
