<?php

namespace App\Selenium\AppleClient\Elements;

use App\Selenium\Repositories\ArrayStore;
use Facebook\WebDriver\WebDriverElement;

class Phone
{

    public function __construct(protected int $id, protected int|string $phone,protected ?WebDriverElement $element = null)
    {
    }

    public function setElement(?WebDriverElement $element = null): void
    {
        $this->element = $element;
    }

    public function getElement(): WebDriverElement
    {
        return $this->element;
    }



    public function getPhone(): int|string
    {
        return $this->phone;
    }
    /**
     * 获取电话号码 ID
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * 获取电话号码 ID
     */
    public function getNumberWithDialCode(): int|string
    {
        return $this->phone;
    }
}


