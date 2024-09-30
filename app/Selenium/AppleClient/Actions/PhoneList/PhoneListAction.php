<?php

namespace App\Selenium\AppleClient\Actions\PhoneList;

use App\Selenium\Actions\Actions;
use App\Selenium\AppleClient\Elements\Phone;
use App\Selenium\AppleClient\Elements\PhoneList;
use App\Selenium\Page\Page;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriverExpectedCondition;

class PhoneListAction extends Actions
{
    protected PhoneList $phoneLists;

    public function __construct(protected Page $page,protected PhoneListStrategyInterface $listStrategy){

        parent::__construct($page);
        $this->phoneLists = new PhoneList();
    }

    /**
     * @return PhoneList
     * @throws \Exception
     */
    public function perform(): PhoneList
    {

        try {

            $ulGroup = $this->page->webDriver()->wait()->until(
                WebDriverExpectedCondition::presenceOfElementLocated(
                    $this->listStrategy->containerSelector()
                )
            );

            // 2. 获取所有的 li 元素
            $liElements = $ulGroup->findElements($this->listStrategy->itemSelector());

            foreach ($liElements as $index => $liElement) {

                $index++;

                try {

                    $phoneElement = $liElement->findElement($this->listStrategy->phoneSelector());

                    $this->phoneLists->put($this->listStrategy->keyGenerator($index, $phoneElement), new Phone($index,$phoneElement));

                } catch (NoSuchElementException $e) {
                    continue;
                }
            }

            return $this->phoneLists;

        } catch (NoSuchElementException|TimeoutException $e) {

            return $this->phoneLists;
        }
    }
}
