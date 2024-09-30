<?php

namespace App\Selenium\AppleClient\Page\AccountManage;

use App\Selenium\AppleClient\Actions\PhoneList\ModalCardListStrategy;
use App\Selenium\AppleClient\Actions\PhoneList\PhoneListAction;
use App\Selenium\AppleClient\Elements\PhoneList;
use App\Selenium\AppleClient\Page\ModalPage;
use App\Selenium\Contract\ArrayStoreContract;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Illuminate\Support\Collection;

class AccountSecurityPage extends ModalPage
{
    public function resolveRootElement(): WebDriverBy
    {
        return WebDriverBy::cssSelector('aside.modal.modal-blurry-overlay');
    }

    public function getPhoneList():PhoneList
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

        return new AddTrustedPhoneNumbersPage($this->connector);
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
