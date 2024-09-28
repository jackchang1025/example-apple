<?php

namespace App\Selenium\AppleClient\Actions\PhoneList;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;

class ModalCardListStrategy implements PhoneListStrategyInterface
{

    public function containerSelector(): WebDriverBy
    {
        return WebDriverBy::cssSelector('.modal-content .modal-body ul.card-list');
    }

    public function itemSelector(): WebDriverBy
    {
        return WebDriverBy::cssSelector('li.card-list-item');
    }

    public function phoneSelector(): WebDriverBy
    {
        return WebDriverBy::cssSelector('.card-title .text bdo');
    }

    public function keyGenerator(int $index,WebDriverElement $phoneElement): int|string
    {
        return $phoneElement->getText();
    }
}
