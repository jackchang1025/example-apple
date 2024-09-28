<?php

namespace App\Selenium;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverCapabilities;
use Symfony\Component\Panther\ProcessManager\BrowserManagerInterface;

class SeleniumManager implements BrowserManagerInterface
{
    public function __construct(
        protected ?string $host = 'http://127.0.0.1:4444/wd/hub',
        protected ?WebDriverCapabilities $capabilities = null,
        protected ?array $options = [],
        protected ?string $sessionId = null,
        protected bool $isW3cCompliant = true
    ) {
        $this->capabilities ??= DesiredCapabilities::chrome();
    }

    public function start(): WebDriver
    {
        return $this->sessionId ? $this->createBySessionID() : $this->create();
    }

    public function createBySessionID(): RemoteWebDriver
    {
        return RemoteWebDriver::createBySessionID(
            $this->sessionId,
            $this->host,
            $this->options['connection_timeout_in_ms'] ?? null,
            $this->options['request_timeout_in_ms'] ?? null,
             $this->isW3cCompliant,
             $this->capabilities,
        );
    }

    public function create(): RemoteWebDriver
    {
        return RemoteWebDriver::create(
            selenium_server_url:$this->host,
            desired_capabilities:$this->capabilities,
            connection_timeout_in_ms:$this->options['connection_timeout_in_ms'] ?? null,
            request_timeout_in_ms:$this->options['request_timeout_in_ms'] ?? null
        );
    }

    public function quit(): void
    {
        // nothing
    }
}
