<?php

namespace App\Selenium\AppleClient\Page;

use App\Selenium\Page\Page;
use Facebook\WebDriver\WebDriverBy;

abstract class ModalPage extends Page
{

    public function resolveRootElement(): WebDriverBy
    {
        return WebDriverBy::cssSelector('aside.modal.modal-blurry-overlay.modal-alert');
    }

    public function modalDialogSelector(): WebDriverBy
    {
        return WebDriverBy::cssSelector('div.modal-dialog');
    }

    public function defaultExceptionSelector(): ?WebDriverBy
    {
        // TODO: Implement defaultExceptionSelector() method.
    }

    public function getTitle(): string
    {
        return $this->findElement($this->modalDialogSelector())
            ->getAttribute('aria-label');
    }

    public function isVisible(): bool
    {
        return $this->findRootElement()
                ->getAttribute('aria-hidden') === 'true';
    }

    public function fillInputField(WebDriverBy $selector, string $value,$times = 3): void
    {
        $this->retry($times,function() use ($selector, $value) {
            $element = $this->webDriver()->findElement($selector);
            $element->click();
            $element->clear();
            $element->sendKeys($value);
        });
    }

    public function clickButton(WebDriverBy $selector,$times = 3): void
    {
        $this->retry($times,function() use ($selector) {
            $button = $this->webDriver()->findElement($selector);
            $button->click();
        });
    }
}
