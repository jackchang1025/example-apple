<?php

namespace App\Selenium\Page;

use App\Selenium\Exception\ElementNotVisibleException;
use App\Selenium\Trait\Conditionable;
use App\Selenium\Trait\HasConfig;
use App\Selenium\Trait\HasRetry;
use App\Selenium\Trait\HasScreenshot;
use App\Selenium\Trait\HasWait;
use App\Selenium\Trait\HasWebDriver;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;

abstract class Page
{
    use HasFindElement;
    use HasRetry;
    use HasWait;
    use HasConfig;
    use HasError;
    use HasScreenshot;
    use HasWebDriver;
    use Conditionable;

    public function __construct(protected readonly WebDriver $driver)
    {
        $this->driver->switchTo()->defaultContent();

        $this->ensureAsideIsVisible();
    }

    protected function ensureAsideIsVisible(): void
    {
        if ($this->isVisible()) {
            throw new ElementNotVisibleException("{$this->getTitle()} modal is hidden");
        }
    }

    public function getTitle(): string
    {
        return $this->driver->getTitle();
    }

    public function webDriver(): WebDriver
    {
        return $this->driver;
    }

    public function findRootElement(): WebDriverElement
    {
        return $this->driver->findElement($this->resolveRootElement());
    }

    abstract public function resolveRootElement(): WebDriverBy;

    /**
     * @return bool
     * @throws NoSuchElementException|TimeoutException
     */
    public function isVisible(): bool
    {
        try {

            $this->driver->wait()->until(
                WebDriverExpectedCondition::presenceOfElementLocated($this->resolveRootElement())
            );

            return false;
        } catch (NoSuchElementException|TimeoutException $e) {
            return true;
        }
    }
}
