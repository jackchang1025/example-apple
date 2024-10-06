<?php

namespace App\Selenium\AppleClient\Page\AccountManage;


use App\Selenium\AppleClient\Page\ModalPage;
use App\Selenium\Exception\PageException;
use App\Selenium\Page\Page;
use Facebook\WebDriver\WebDriverBy;

class ConfirmPasswordPage extends ModalPage
{
    private const string INPUT_SELECTOR = '.modal-form .modal-body input.form-textbox-input';

    private const string SUBMIT_BUTTON_SELECTOR = '.modal-form .modal-button-bar .button.button-rounded-rectangle[type="submit"]';
    private const string CANCEL_BUTTON_SELECTOR = '.modal-form .modal-button-bar .button.button-secondary.button-rounded-rectangle';

    public function defaultExceptionSelector(): ?WebDriverBy
    {
        return WebDriverBy::cssSelector('.modal-form .modal-body .form-message-wrapper span.form-message');
    }

    public function inputConfirmPassword(string $confirmPassword): void
    {
        $this->fillInputField(WebDriverBy::cssSelector(self::INPUT_SELECTOR), $confirmPassword);
    }

    public function submit(): ValidateTrustedCodePage
    {
        try {
            $this->clickButton(WebDriverBy::cssSelector(self::SUBMIT_BUTTON_SELECTOR));
            $this->throw();

            return new ValidateTrustedCodePage($this->connector);
        } catch (PageException $e) {

            $this->takeScreenshot('confirm-password.png');
            throw $e;
        }
    }

    public function cancel(): AccountSecurityPage
    {
        $this->clickButton(WebDriverBy::cssSelector(self::CANCEL_BUTTON_SELECTOR));
        return  new AccountSecurityPage($this->connector);
    }
}
