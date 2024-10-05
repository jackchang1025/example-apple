<?php

namespace App\Selenium\AppleClient\Page\SignIn;

use App\Selenium\AppleClient\Actions\PhoneList\DeviceContainerStrategy;
use App\Selenium\AppleClient\Actions\PhoneList\PhoneListAction;
use App\Selenium\AppleClient\Elements\Phone;
use App\Selenium\AppleClient\Elements\PhoneList;
use App\Selenium\AppleClient\Page\IframePage;
use App\Selenium\Exception\PageException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Illuminate\Support\Collection;

class SignInSelectPhonePage extends IframePage
{

    public function title(): string
    {
        return 'Verify Your Identity';
    }

    public function getTitle(): string
    {
        return $this->findElement(WebDriverBy::cssSelector('h1.tk-callout'))->getText();
    }

    /**
     * @return PhoneList
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeoutException
     */
    public function getPhoneLists(): PhoneList
    {
        if ($phoneList = $this->config()->get('phone_list')){
            return $phoneList;
        }

        try {

            $PhoneListAction = new PhoneListAction($this, new DeviceContainerStrategy());
            $phoneList       = $PhoneListAction->perform();

        } catch (NoSuchElementException|TimeoutException $e) {

            $this->takeScreenshot('SignInSelectPhonePage.png');

            throw $e;
        }

        $this->config()->add('phone_list',$phoneList);

        return $phoneList;
    }

    public function defaultExceptionSelector(): WebDriverBy
    {
        return WebDriverBy::cssSelector('.form-tooltip-info p.form-tooltip-content');
    }

    /**
     * @param int $id
     * @return TwoFactorAuthenticationPage
     * @throws NoSuchElementException
     * @throws PageException
     * @throws TimeoutException
     */
    public function selectPhone(int $id): TwoFactorAuthenticationPage
    {

        /**
         * @var Phone $phoneElement
         */
        $phoneElement = $this->getPhoneLists()->get($id);

        if (!$phoneElement) {
            throw new PageException($this, "Phone id $id not found");
        }

        $phoneElement->getElement()->click();

        return new TwoFactorAuthenticationPage($this->connector);
    }
}
