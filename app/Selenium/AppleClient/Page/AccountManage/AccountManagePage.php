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

    protected ?ArrayStoreContract $pageSectionActions = null;

    public function resolveRootElement(): WebDriverBy
    {
        return WebDriverBy::id('root');
    }

    public function defaultExceptionSelector(): WebDriverBy
    {
        return WebDriverBy::cssSelector('.error.pop-bottom.tk-subbody-headline p#errMsg');
    }

    /**
     * @return ArrayStoreContract
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    public function getPageSectionActions(): ArrayStoreContract
    {
        return $this->pageSectionActions ??= new ArrayStore($this->performPageSectionActions());
    }

    /**
     * @return array
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    protected function performPageSectionActions(): array
    {
        return $this->driver->wait()->until(
            WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(
                WebDriverBy::cssSelector('.section .button.button-bare.button-expand.button-rounded-rectangle')
            )
        );
    }

    /**
     * @return AccountSecurityPage
     * @throws \Exception
     */
    public function switchToPhoneListPage(): AccountSecurityPage
    {
        try {

            if (!$phoneListAction = $this->getPageSectionActions()->get(2)) {
                throw new \RuntimeException('button not found');
            }
            $phoneListAction->click();

            return new AccountSecurityPage($this->connector);

        } catch (\Exception $e) {

            $this->takeScreenshot("switchToPhoneListPage.png");

            throw $e;
        }
    }
}
