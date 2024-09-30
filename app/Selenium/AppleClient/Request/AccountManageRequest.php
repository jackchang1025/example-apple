<?php

namespace App\Selenium\AppleClient\Request;

use App\Selenium\AppleClient\Page\AccountManage\AccountManagePage;
use App\Selenium\PendingRequest;
use App\Selenium\Request\Method;
use App\Selenium\Request\Request;

class AccountManageRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return 'https://account.apple.com/account/manage';
    }

    public function actions(PendingRequest $pendingRequest): ?PendingRequest
    {
        $connector = $pendingRequest->getConnector();

        $client = $pendingRequest->getConnector()->client();

        $AccountManagePage = new AccountManagePage($connector);

        $pendingRequest->setPage($AccountManagePage);

//        $phoneListPage = $AccountManagePage->switchToPhoneListPage();
//
//        $addTrustedPhoneNumbersPage = $phoneListPage->switchToAddTrustedPhoneNumbersPage();
//
//        $addTrustedPhoneNumbersPage->selectByValue('CN');
//        $addTrustedPhoneNumbersPage->inputTel($this->phoneNumber);
//        $addTrustedPhoneNumbersPage->selectRadioSmsButton();
//
//        $confirmPasswordPage = $addTrustedPhoneNumbersPage->submit();
//
//        $confirmPasswordPage->inputConfirmPassword($this->password);
//        $ValidateCodePage = $confirmPasswordPage->submit();

        return $pendingRequest;
    }
}
