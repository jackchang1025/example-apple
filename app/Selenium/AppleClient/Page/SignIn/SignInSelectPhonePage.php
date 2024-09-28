<?php

namespace App\Selenium\AppleClient\Page\SignIn;

use App\Selenium\AppleClient\Actions\PhoneList\DeviceContainerStrategy;
use App\Selenium\AppleClient\Actions\PhoneList\PhoneListAction;
use App\Selenium\AppleClient\Page\IframePage;
use App\Selenium\Contract\ArrayStoreContract;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use InvalidArgumentException;

class SignInSelectPhonePage extends IframePage
{

    public function getTitle(): string
    {
        return $this->findElement(WebDriverBy::cssSelector('.choose-phone h1.tk-callout'))->getText();
    }

    public function getPhoneLists():ArrayStoreContract
    {
        if ($phoneList = $this->config()->get('phone_list')){
            return $phoneList;
        }

        $PhoneListAction = new PhoneListAction($this,new DeviceContainerStrategy());
        $phoneList = $PhoneListAction->perform();

        $this->config()->add('phone_list',$phoneList);

        return $phoneList;
    }

    public function defaultExceptionSelector(): WebDriverBy
    {
        return WebDriverBy::cssSelector('.form-tooltip-info p.form-tooltip-content');
    }

    public function selectPhone(int $id)
    {
        /**
         * @var ?WebDriverElement $phoneElement
         */
        $phoneElement = $this->getPhoneLists()->get($id);

        if (!$phoneElement){
            throw new InvalidArgumentException("Phone id $id not found");
        }

        $phoneElement->click();

        return new SignInAuthPage($this->webDriver());
    }

    public function getPhoneList(int $id):?WebDriverElement
    {
        return $this->getPhoneLists()->get($id);
    }

//    public function resolveRootElement(): WebDriverBy
//    {
//        return WebDriverBy::cssSelector('.widget-container.fade-in.restrict-min-content.restrict-max-wh.fade-in');
//    }

    //
}
