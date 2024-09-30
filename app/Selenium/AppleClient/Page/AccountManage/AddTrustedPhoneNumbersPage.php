<?php

namespace App\Selenium\AppleClient\Page\AccountManage;

use App\Selenium\AppleClient\Page\ModalPage;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\UnexpectedTagNameException;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverSelect;

class AddTrustedPhoneNumbersPage extends ModalPage
{
    private const SELECT_SELECTOR = '.modal-body .form-dropdown .form-dropdown-select';
    private const TEL_INPUT_SELECTOR = 'input.form-textbox-input.form-textbox-input-ltr';
    private const SMS_RADIO_SELECTOR = '.verify-phone-by input[value="sms"]';
    private const VOICE_RADIO_SELECTOR = '.verify-phone-by input[value="voice"]';
    private const SUBMIT_BUTTON_SELECTOR = '.button-bar-nav button[type="submit"]';

    /**
     * @param string $value
     * @return void
     * @throws NoSuchElementException
     * @throws UnexpectedTagNameException
     */
    public function selectByValue(string $value): void
    {
        // 创建 Select 对象
        $selectElement = new WebDriverSelect(
            $this->webDriver()->findElement(
                WebDriverBy::cssSelector(self::SELECT_SELECTOR)
            )
        );

        // 通过可见文本选择选项
        $selectElement->selectByValue($value);
    }

    /**
     * @param string $text
     * @return void
     * @throws NoSuchElementException
     * @throws UnexpectedTagNameException
     */
    public function selectByVisibleText(string $text = '+86 (中国大陆)'): void
    {
        // 创建 Select 对象
        $selectElement = new WebDriverSelect(
            $this->webDriver()->findElement(
                WebDriverBy::cssSelector(self::SELECT_SELECTOR)
            )
        );

        // 通过可见文本选择选项
        $selectElement->selectByVisibleText($text);
    }

    public function inputTel(string $tel): void
    {
        $this->fillInputField(WebDriverBy::cssSelector(self::TEL_INPUT_SELECTOR), $tel);
    }

    public function submit(): ModalPage
    {
        $this->clickButton(WebDriverBy::cssSelector(self::SUBMIT_BUTTON_SELECTOR));

        $this->throw();

        $confirmPasswordPage = new ConfirmPasswordPage($this->connector);
        if ($confirmPasswordPage->getTitle() === 'Confirm Your Password'){
            return $confirmPasswordPage;
        }
        return new ValidateTrustedCodePage($this->connector);
    }

    public function defaultExceptionSelector(): WebDriverBy
    {
        return WebDriverBy::cssSelector('.modal-form .modal-body .form-message-wrapper span.form-message');
    }


    /**
     * 选择短信验证方式的单选按钮。
     *
     * @return WebDriverElement 返回已选择或已点击的单选按钮元素
     * @throws NoSuchElementException 如果找不到指定的单选按钮元素。
     * @throws WebDriverException 如果在操作单选按钮时发生错误。
     */
    public function selectRadioSmsButton(): \Facebook\WebDriver\WebDriverElement
    {
        // 找到单选按钮元素
        $radioButton = $this->webDriver()->findElement(WebDriverBy::cssSelector(self::SMS_RADIO_SELECTOR));

        // 如果单选按钮未被选中，则点击它
        if (!$radioButton->isSelected()) {
            $radioButton->click();
        }
        return $radioButton;
    }

    /**
     * 根据条件选择语音验证的单选按钮。
     *
     * 查找具有指定属性的单选按钮元素，如果该按钮尚未被选中，则点击它。
     * 最后返回操作后的单选按钮元素实例。
     *
     * @return RemoteWebElement 返回操作后的单选按钮元素实例。
     * @throws NoSuchElementException 如果找不到匹配的单选按钮元素。
     * @throws WebDriverException 如果在操作过程中发生错误。
     */
    public function selectRadioVoiceButton(): WebDriverElement
    {
        // 找到单选按钮元素
        $radioButton = $this->webDriver()->findElement(WebDriverBy::cssSelector(self::VOICE_RADIO_SELECTOR));

        // 如果单选按钮未被选中，则点击它
        if (!$radioButton->isSelected()) {
            $radioButton->click();
        }

        return $radioButton;
    }

}
