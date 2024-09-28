<?php

namespace App\Selenium\AppleClient\Actions\PhoneList;

use App\Selenium\Actions\Actions;
use App\Selenium\AppleClient\Elements\Phone;
use App\Selenium\Contract\ArrayStoreContract;
use App\Selenium\Page\Page;
use App\Selenium\Repositories\ArrayStore;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriverExpectedCondition;

class PhoneListAction extends Actions
{
    protected ArrayStoreContract $phoneLists;

    public function __construct(protected Page $page,protected PhoneListStrategyInterface $listStrategy){

        parent::__construct($page);
        $this->phoneLists = new ArrayStore();
    }

    /**
     * @return ArrayStoreContract
     * @throws \Exception
     */
    public function perform(): ArrayStoreContract
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

                try {

                    $phoneElement = $liElement->findElement($this->listStrategy->phoneSelector());

                    $this->phoneLists->add($this->listStrategy->keyGenerator($index, $liElement), new Phone($index,$phoneElement->getText(),$phoneElement));

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
