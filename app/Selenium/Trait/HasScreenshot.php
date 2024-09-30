<?php

namespace App\Selenium\Trait;

use Carbon\Carbon;

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

    protected function sprintf(string $fileName): string
    {
        return sprintf('%s/%s-%s', $this->getPath(), Carbon::now()->format('Y-m-d-H-i-s'),$fileName);
    }

    public function takeScreenshot(string $fileName = 'screenshot.png'): string
    {
        $this->webDriver()->takeScreenshot($path = $this->sprintf($fileName));

        return $path;
    }

}
