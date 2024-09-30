<?php

namespace App\Selenium\AppleClient\Page\SignIn;

use App\Apple\Service\Exception\VerificationCodeIncorrect;
use App\Selenium\AppleClient\Actions\InputTrustedCodeAction;
use App\Selenium\AppleClient\Page\AccountManage\AccountManagePage;
use App\Selenium\AppleClient\Page\IframePage;
use App\Selenium\Exception\PageException;
use App\Selenium\Page\Page;
use Facebook\WebDriver\WebDriverBy;


class TwoFactorAuthenticationPage extends IframePage
{

    public function title(): string
    {
        return 'Two-Factor Authentication';
    }

    /**
     * @param string $code
     * @return AccountManagePage
     * @throws PageException
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
