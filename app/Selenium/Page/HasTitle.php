<?php

namespace App\Selenium\Page;

trait HasTitle
{
    public function title(): string
    {
        return '';
    }

    public function hasTitle(string $title): bool
    {
        return $title === $this->getTitle();
    }

    public function isCurrentTitle(): bool
    {
        return $this->hasTitle($this->title());
    }

}
