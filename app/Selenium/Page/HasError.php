<?php

namespace App\Selenium\Page;

use App\Selenium\Exception\PageErrorException;
use App\Selenium\Exception\PageException;
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

    public function defaultException(Page $page,string $message): PageException
    {
        return new PageException($page, $message);
    }

    public function toException(): ?PageException
    {
        $exceptionElement = $this->getExceptionElement();
        if ($exceptionElement && !empty($exceptionElement->getText())) {
            return $this->defaultException($this,$exceptionElement->getText());
        }

        $alertInfoElement = $this->getAlertInfoElement();
        if ($alertInfoElement && !empty($alertInfoElement->getText())) {
            return $this->defaultException($this,$alertInfoElement->getText());
        }

        return null;
    }


    /**
     * @return void
     * @throws PageException
     */
    public function throw(): void
    {
        if ($exception = $this->toException()){
            throw $exception;
        }
    }

    public function throwIf(mixed $condition = null): void
    {
        $exception = $this->toException();

        if (is_callable($condition) && $exception) {
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
