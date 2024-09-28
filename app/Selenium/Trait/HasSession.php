<?php

namespace App\Selenium\Trait;

use Facebook\WebDriver\Remote\RemoteWebDriver;

trait HasSession
{
    use HasWebDriver;

    protected ?string $session = null;

    public function getSession(): string
    {
        return $this->session ??= $this->resetSession();
    }

    public function getSessionNotResetSession(): ?string
    {
        return $this->session;
    }

    public function setSession(?string $session): void
    {
        $this->session = $session;
    }

    public function resetSession(): string
    {
        /**
         * @var RemoteWebDriver $webDriver
         */
        $webDriver = $this->webDriver();

        return $this->session = $webDriver->getSessionID();
    }
}
