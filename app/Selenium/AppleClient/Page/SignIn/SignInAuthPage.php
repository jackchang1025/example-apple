<?php

namespace App\Selenium\AppleClient\Page\SignIn;

use App\Selenium\AppleClient\Actions\InputTrustedCodeAction;
use App\Selenium\AppleClient\Page\AccountManage\AccountManagePage;
use App\Selenium\AppleClient\Page\IframePage;
use Facebook\WebDriver\WebDriverBy;


class SignInAuthPage extends IframePage
{

    public function inputTrustedCode(string $code):AccountManagePage
    {
        $InputTrustedCodeAction = new InputTrustedCodeAction(
            page: $this,
            locator: WebDriverBy::cssSelector('.form-security-code-input'),
            code: $code
        );

        $InputTrustedCodeAction->perform();

        $this->throw();

        return new AccountManagePage($this->driver);
    }

    public function getTitle(): string
    {
        return $this->findElement(WebDriverBy::cssSelector('.verify-device h1.tk-callout'))->getText();
    }

    public function defaultExceptionSelector(): WebDriverBy
    {
        return WebDriverBy::cssSelector('.form-message-wrapper span.form-message');
    }

//    public function resolveRootElement(): WebDriverBy
//    {
//        return WebDriverBy::id('content');
//    }
}
