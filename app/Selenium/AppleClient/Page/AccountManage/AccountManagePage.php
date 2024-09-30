<?php

namespace App\Selenium\AppleClient\Page\AccountManage;

use App\Selenium\Contract\ArrayStoreContract;
use App\Selenium\Page\Page;
use App\Selenium\Repositories\ArrayStore;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class AccountManagePage extends Page
{

    public function resolveRootElement(): WebDriverBy
    {
        return WebDriverBy::id('root');
    }

    public function defaultExceptionSelector(): WebDriverBy
    {
        return WebDriverBy::cssSelector('.error.pop-bottom.tk-subbody-headline p#errMsg');
    }

    public function getPageSectionActions(): ArrayStoreContract
    {
        if ($pageSectionAction = $this->config()->get('pageSectionAction')){
            return $pageSectionAction;
        }

        $pageSectionAction = new ArrayStore($this->performPageSectionActions());

        $this->config()->add('pageSectionAction',$pageSectionAction);

        return $pageSectionAction;
    }

    protected function performPageSectionActions(): array
    {
        try {

            return $this->driver->wait(10)->until(
                WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(WebDriverBy::cssSelector('.section .button.button-bare.button-expand.button-rounded-rectangle'))
            );

        } catch (NoSuchElementException|TimeoutException $e) {
            return [];
        }
    }

    public function switchToPhoneListPage(): AccountSecurityPage
    {
        if (!$phoneListAction = $this->getPageSectionActions()->get(2)){
            throw new \RuntimeException('button not found');
        }

        $phoneListAction->click();
        return new AccountSecurityPage($this->connector);
    }
}
