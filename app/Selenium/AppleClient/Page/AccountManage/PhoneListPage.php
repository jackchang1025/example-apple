<?php

namespace App\Selenium\AppleClient\Page\AccountManage;

use App\Selenium\AppleClient\Actions\PhoneList\ModalCardListStrategy;
use App\Selenium\AppleClient\Actions\PhoneList\PhoneListAction;
use App\Selenium\AppleClient\Elements\PhoneLists;
use App\Selenium\AppleClient\Page\ModalPage;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class PhoneListPage extends ModalPage
{
    public function resolveRootElement(): WebDriverBy
    {
        return WebDriverBy::cssSelector('aside.modal.modal-blurry-overlay');
    }

    public function getPhoneList():PhoneLists
    {
        if ($phoneList = $this->config()->get('phoneList')){
            return $phoneList;
        }

        $PhoneListAction = new PhoneListAction($this,new ModalCardListStrategy());

        $phoneList = $PhoneListAction->perform();
        $this->config()->add('phoneList',$phoneList);

        return $phoneList;
    }

    public function switchToAddTrustedPhoneNumbersPage(): AddTrustedPhoneNumbersPage
    {
        //等待模态框弹出
        $addBindPhoneButton = $this->getAddBindPhoneButtonAction();
        $addBindPhoneButton->click();

        return new AddTrustedPhoneNumbersPage($this->driver);
    }

    public function getAddBindPhoneButtonAction()
    {
        return $this->driver->wait()->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('.modal-content .modal-body .button.button-icon.button-rounded-rectangle')
            )
        );
    }

}
