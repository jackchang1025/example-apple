<?php

namespace App\Selenium\AppleClient\Request;

use App\Selenium\AppleClient\Exception\AccountException;
use App\Selenium\AppleClient\Exception\AccountLockoutException;
use App\Selenium\AppleClient\Page\SignIn\SignInPage;
use App\Selenium\PendingRequest;
use App\Selenium\Request\Method;
use App\Selenium\Request\Request;

class SignInRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return 'https://account.apple.com/sign-in';
    }

    /**
     * @param PendingRequest $pendingRequest
     * @return PendingRequest|null
     * @throws AccountException
     * @throws AccountLockoutException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeoutException
     */
    public function actions(PendingRequest $pendingRequest): ?PendingRequest
    {
        $connector = $pendingRequest->getConnector();

        $client = $pendingRequest->getConnector()->client();

        $signInPage = new SignInPage($connector);

        $pendingRequest->setPage($signInPage);

        return $pendingRequest;
    }
}
