<?php

namespace App\Selenium\AppleClient\Page\AccountManage;

use App\Selenium\AppleClient\Actions\PhoneList\ModalCardListStrategy;
use App\Selenium\AppleClient\Actions\PhoneList\PhoneListAction;
use App\Selenium\AppleClient\Elements\PhoneList;
use App\Selenium\AppleClient\Page\ModalPage;
use App\Selenium\Contract\ArrayStoreContract;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Illuminate\Support\Collection;

class AccountSecurityPage extends ModalPage
{
    protected ?PhoneList $phoneList = null;

    public function resolveRootElement(): WebDriverBy
    {
        return WebDriverBy::cssSelector('aside.modal.modal-blurry-overlay');
    }

    /**
     * @return PhoneList
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    public function getPhoneList():PhoneList
    {
        return $this->phoneList ??= (new PhoneListAction($this,new ModalCardListStrategy()))->perform();
    }

    /**
     * @return AddTrustedPhoneNumbersPage
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    public function switchToAddTrustedPhoneNumbersPage(): AddTrustedPhoneNumbersPage
    {
        try {

            $addBindPhoneButton = $this->getAddBindPhoneButtonAction();
            $addBindPhoneButton->click();

        } catch (\Exception $e) {

            $this->takeScreenshot("switchToAddTrustedPhoneNumbersPage.png");

            throw $e;
        }

        return new AddTrustedPhoneNumbersPage($this->connector);
    }

    /**
     * @return WebDriverElement
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    public function getAddBindPhoneButtonAction():WebDriverElement
    {
        return $this->driver->wait()->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('.modal-content .modal-body .button.button-icon.button-rounded-rectangle')
            )
        );
    }

}
