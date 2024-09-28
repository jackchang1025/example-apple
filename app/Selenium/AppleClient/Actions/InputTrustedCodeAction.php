<?php

namespace App\Selenium\AppleClient\Actions;

use App\Selenium\Actions\Actions;
use App\Selenium\Page\Page;
use Facebook\WebDriver\WebDriverBy;

class InputTrustedCodeAction extends Actions
{
    public function __construct(protected Page $page,protected WebDriverBy $locator,protected string $code){

        parent::__construct($page);
    }

    public function perform(): true
    {
        // 找到所有的验证码输入框
        $inputFields = $this->page->webDriver()->findElements($this->locator);

        // 确保找到了 6 个输入框
        if (count($inputFields) !== 6) {
            throw new \RuntimeException("Expected 6 input fields, but found " . count($inputFields));
        }

        // 分割验证码并输入到各个输入框
        foreach (str_split($this->code) as $index => $digit){

            $inputField = $inputFields[$index];

            // 点击输入框以设置焦点
            $inputField->click();

            // 清除输入框中的现有内容
            $inputField->clear();

            // 输入验证码数字
            $inputField->sendKeys($digit);

            // 短暂等待，模拟人工输入
            usleep(200000); // 等待 0.2 秒
        }

        return true;
    }
}
