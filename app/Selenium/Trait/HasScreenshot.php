<?php

namespace App\Selenium\Trait;

trait HasScreenshot
{
    use HasWebDriver;

    protected ?string $path = null;

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path ??= $this->defaultScreenshotPath();
    }

    public function defaultScreenshotPath(): string
    {
        return '';
    }

    public function filePath(string $fileName): string
    {
        return sprintf('%s/%s', $this->getPath(),$fileName);
    }

    public function takeScreenshot(string $fileName = 'screenshot.png'): string
    {
        $this->webDriver()->takeScreenshot($path = $this->filePath($fileName));

        return $path;
    }

}
