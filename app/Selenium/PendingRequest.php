<?php

declare(strict_types=1);

namespace App\Selenium;

use App\Selenium\Helpers\BootPlugins;
use App\Selenium\Helpers\URLHelper;
use App\Selenium\Page\Page;
use App\Selenium\Request\Method;
use App\Selenium\Request\Request;
use App\Selenium\Trait\Conditionable;
use App\Selenium\Trait\Macroable;
use Symfony\Component\Panther\DomCrawler\Crawler as PantherCrawler;

class PendingRequest
{
    use Conditionable;
    use Macroable;

    /**
     * The request used by the instance.
     */
    protected Request $request;
    protected Page $page;

    /**
     * The method the request will use.
     */
    protected Method $method;

    protected Connector $connector;

    protected PantherCrawler $crawler;

    protected array $parameters = [];

    protected array $files = [];

    protected array $server = [];

    protected ?string $content = null;

    protected bool $changeHistory = true;

    /**
     * The URL the request will be made to.
     */
    protected string $url;


    /**
     * Build up the request payload.
     */
    public function __construct(Connector $connector, Request $request)
    {
        // Let's start by getting our PSR factory collection. This object contains all the
        // relevant factories for creating PSR-7 requests as well as URIs and streams.

//        $this->factoryCollection = $connector->sender()->getFactoryCollection();

        // Now we'll set the base properties

        $this->connector = $connector;
        $this->request   = $request;
        $this->method    = $request->getMethod();
        $this->url       = URLHelper::join($this->connector->resolveBaseUrl(), $this->request->resolveEndpoint());

        $this
            ->tap(new BootPlugins);
    }

    public function setCrawler(PantherCrawler $crawler): void
    {
        $this->crawler = $crawler;
    }

    public function getCrawler(): PantherCrawler
    {
        return $this->crawler;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setFiles(array $files): void
    {
        $this->files = $files;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function setServer(array $server): void
    {
        $this->server = $server;
    }

    public function getServer(): array
    {
        return $this->server;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setChangeHistory(bool $changeHistory): void
    {
        $this->changeHistory = $changeHistory;
    }

    public function isChangeHistory(): bool
    {
        return $this->changeHistory;
    }



    /**
     * Get the HTTP method used for the request
     */
    public function getMethod(): Method
    {
        return $this->method;
    }

    /**
     * Set the method of the PendingRequest
     *
     * @return $this
     */
    public function setMethod(Method $method): static
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Tap into the pending request
     *
     * @return $this
     */
    protected function tap(callable $callable): static
    {
        $callable($this);

        return $this;
    }

    /**
     * Get the request.
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get the connector.
     */
    public function getConnector(): Connector
    {
        return $this->connector;
    }

    /**
     * Get the URL of the request.
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    public function setPage(Page $page): void
    {
        $this->page = $page;
    }

    public function getPage(): Page
    {
        return $this->page;
    }

    /**
     * Set the URL of the PendingRequest
     *
     * Note: This will be combined with the query parameters to create
     * a UriInterface that will be passed to a PSR-7 request.
     *
     * @return $this
     */
    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }
}
