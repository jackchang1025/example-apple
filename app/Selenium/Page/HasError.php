<?php

namespace App\Selenium\Page;

use App\Selenium\Exception\PageErrorException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;

trait HasError
{
    public function defaultExceptionSelector():?WebDriverBy
    {
        return null;
    }

    public function defaultAlertInfoSelector():?WebDriverBy
    {
        return null;
    }

    public function toException(): ?PageErrorException
    {
        $exceptionElement = $this->getExceptionElement();
        if ($exceptionElement) {
            return new PageErrorException($this, $exceptionElement, $exceptionElement->getText());
        }

        $alertInfoElement = $this->getAlertInfoElement();
        if ($alertInfoElement) {
            return new PageErrorException($this, $alertInfoElement, $alertInfoElement->getText());
        }

        return null;
    }


    public function throw(): void
    {
        if ($exception = $this->toException()){
            throw new $exception;
        }
    }

    public function throwIf(mixed $condition = null): void
    {
        $exception = $this->toException();

        if (is_callable($condition)) {
            $condition = $condition($this, $exception);
        }

        if ($condition && $exception) {
            throw $exception;
        }
    }

    public function getExceptionElement(?WebDriverBy $selector = null):?WebDriverElement
    {
        $selector ??= $this->defaultExceptionSelector();

        if ($selector === null){
            return null;
        }

        return $this->waitForWebDriverNotException($selector,3);
    }

    public function getAlertInfoElement(?WebDriverBy $selector = null):?WebDriverElement
    {
        $selector ??= $this->defaultAlertInfoSelector();

        if ($selector === null){
            return null;
        }

        return $this->waitForWebDriverNotException($selector,3);
    }
}
