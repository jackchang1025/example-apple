<?php

namespace App\Selenium\Trait;

use Closure;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;

trait HasWait
{

    /**
     *
     */
    public function webDriverWait(
        Closure|WebDriverExpectedCondition $condition,
        string $message = '',
        ?int $timeout_in_second = null,
        ?int $interval_in_millisecond = null
    ): mixed {
        return (new WebDriverWait($this->driver, $timeout_in_second, $interval_in_millisecond))
            ->until($condition, $message);
    }


    public function webDriverWaitNotThrow(
        Closure|WebDriverExpectedCondition $condition,
        string $message = '',
        ?int $timeout_in_second = null,
        ?int $interval_in_millisecond = null
    ): mixed {

        try {

            return (new WebDriverWait($this->driver, $timeout_in_second, $interval_in_millisecond))
                ->until($condition, $message);

        } catch (NoSuchElementException|TimeoutException $e) {

            return null;
        }
    }

    public function waitForWebDriverNotException(WebDriverBy $elector, int $timeoutInSeconds = 30): ?RemoteWebElement
    {
        try {

            return $this->driver->wait($timeoutInSeconds)->until(
                WebDriverExpectedCondition::presenceOfElementLocated($elector)
            );

        } catch (NoSuchElementException|TimeoutException $e) {

            return null;
        }
    }


}
