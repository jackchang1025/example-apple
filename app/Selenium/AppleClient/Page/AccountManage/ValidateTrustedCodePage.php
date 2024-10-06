<?php

namespace App\Selenium\AppleClient\Page\AccountManage;

use App\Selenium\AppleClient\Actions\InputTrustedCodeAction;
use App\Selenium\AppleClient\Page\ModalPage;
use App\Selenium\Exception\PageException;
use Facebook\WebDriver\WebDriverBy;

class ValidateTrustedCodePage extends ModalPage
{
    private const string SUBMIT_BUTTON_SELECTOR = '.modal-form .modal-button-bar .button.button-rounded-rectangle[type="submit"]';
    private const string CANCEL_BUTTON_SELECTOR = '.modal-form .modal-button-bar .button.button-secondary.button-rounded-rectangle';
    private const string INPUTS_SELECTOR = '.modal-body .form-security-code-inputs input.form-security-code-input';

    public function inputTrustedCode(string $code): ValidateTrustedCodePage
    {
        $InputTrustedCodeAction = new InputTrustedCodeAction(
            page: $this,
            locator: WebDriverBy::cssSelector(self::INPUTS_SELECTOR),
            code: $code
        );

        $InputTrustedCodeAction->perform();
        return $this;
    }

    public function defaultExceptionSelector(): ?WebDriverBy
    {
        return WebDriverBy::cssSelector('.modal-form .modal-body .form-message-wrapper span.form-message');
    }

    public function submit(): AccountSecurityPage
    {
        try {
            $this->clickButton(WebDriverBy::cssSelector(self::SUBMIT_BUTTON_SELECTOR));
            $this->throw();

            return new AccountSecurityPage($this->connector);
        } catch (PageException $e) {

            $this->takeScreenshot('ValidateTrustedCodePage.png');
            throw $e;
        }
    }

    public function cancel(): void
    {
        $this->clickButton(WebDriverBy::cssSelector(self::CANCEL_BUTTON_SELECTOR));
    }
}
