<?php

namespace App\Apple\Service;

use App\Apple\Service\Exception\VerificationCodeIncorrect;
use App\Apple\Service\User\User;
use App\Apple\Service\User\UserFactory;
use App\Events\AccountAuthFailEvent;
use App\Events\AccountAuthSuccessEvent;
use App\Events\AccountLoginSuccessEvent;
use App\Models\Account;
use App\Selenium\AppleClient\Elements\PhoneList;
use App\Selenium\AppleClient\Page\SignIn\SignInSelectPhonePage;
use App\Selenium\AppleClient\Page\SignIn\TwoFactorAuthWithDevicePage;
use App\Selenium\AppleClient\Page\SignIn\TwoFactorAuthWithPhonePage;
use App\Selenium\AppleClient\Request\SignInRequest;
use App\Selenium\Connector;
use App\Selenium\ConnectorManager;
use App\Selenium\Page\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

readonly class ConnectorService
{
    protected ?string $guid;

    protected Connector $connector;
    protected User $user;

    public function __construct(
        protected UserFactory $userFactory,
        protected ConnectorManager $connectorManager,
        protected Request $request,
    )
    {
        $this->guid = $request->cookie('Guid',$this->request->input('Guid'));

    }

    public function getGuid(): string
    {
        return $this->guid;
    }

    public function getUser(): User
    {
        return $this->user ??= $this->userFactory->create($this->guid);
    }

    public function getConnector(): Connector
    {
        $connector = $this->connectorManager->getConnector($this->guid);

        if (! $account = $this->getUser()->getAccount()){
            throw new \RuntimeException('账号信息不存在');
        }

        $connector->config()
            ->add('screenshot_path',storage_path("/browser/screenshots/{$account->account}/"));

        return $connector;
    }

    /**
     * @param string $accountName
     * @param string $password
     * @return Page
     * @throws \App\Selenium\AppleClient\Exception\AccountException
     * @throws \App\Selenium\Exception\PageErrorException
     * @throws \App\Selenium\Exception\PageException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeoutException
     */
    public function verifyAccount(string $accountName,string $password): Page
    {
        $connector = $this->connectorManager->createConnector();
        $connector->config()
            ->add('screenshot_path',storage_path("/browser/screenshots/{$accountName}/"));

        $response = $connector->send(new SignInRequest());

        /**
         * @var \App\Selenium\AppleClient\Page\SignIn\SignInPage $page
         */
        $page = $response->getPage();

        $authPage = $page->sign($accountName,$password);

        $account = Account::updateOrCreate([
            'account' => $accountName,
        ],[ 'password' => $password]);

        $this->getUser()->setAccount($account);

        Event::dispatch(new AccountLoginSuccessEvent(account: $account,description: "登录成功"));

        $this->connectorManager->add($this->guid,$connector);

        return $authPage;
    }

    /**
     * @return PhoneList
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeoutException
     */
    public function getPhoneLists(): PhoneList
    {
        $connector = $this->getConnector();

        return (new SignInSelectPhonePage($connector))->getPhoneLists();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function getUseDifferentPhoneNumberButton(): bool
    {
        $connector = $this->getConnector();

        $page = new TwoFactorAuthWithPhonePage($connector);

        return (bool) $page->getUseDifferentPhoneNumberButton();
    }

    /**
     * @return TwoFactorAuthWithPhonePage
     */
    public function useDifferentPhoneNumber(): TwoFactorAuthWithPhonePage
    {
        $connector = $this->getConnector();

        $page = new TwoFactorAuthWithPhonePage($connector);
        $page->useDifferentPhoneNumber();

        return $page;
    }

    /**
     * @param int $id
     * @return void
     * @throws \App\Selenium\Exception\PageException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @throws \Facebook\WebDriver\Exception\TimeoutException
     */

    public function sendSms(int $id): void
    {
        $connector = $this->getConnector();

        $page = new SignInSelectPhonePage($connector);
        $page->selectPhone($id);

    }

    public function usePhoneNumber(): SignInSelectPhonePage|Page
    {
        $connector = $this->getConnector();

        return (new TwoFactorAuthWithDevicePage($connector))->usePhoneNumber();
    }

    /**
     * @return void
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     */
    public function resendPhoneCode(): void
    {
        $connector = $this->getConnector();

        (new TwoFactorAuthWithPhonePage($connector))->resendCode();
    }

    /**
     * @return void
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     */
    public function resendSecurityCode(): void
    {
        $connector = $this->getConnector();

        (new TwoFactorAuthWithDevicePage($connector))->resendCode();
    }

    public function smsSecurityCode(string $code)
    {

        // 验证手机号码
        try {

            $connector = $this->getConnector();

            $page = new TwoFactorAuthWithPhonePage($connector);

            $page->inputTrustedCode($code);

            Event::dispatch(new AccountAuthSuccessEvent(account: $this->getUser()->getAccount(),description: "手机验证码验证成功 code:{$code}"));

        } catch (VerificationCodeIncorrect $e) {

            Event::dispatch(new AccountAuthFailEvent(account: $this->getUser()->getAccount(),description: ($e->getMessage())));
            throw $e;
        }
    }

    public function verifySecurityCode(string $code)
    {
        try {

            $connector = $this->getConnector();

            $page = new TwoFactorAuthWithDevicePage($connector);

            $page->inputTrustedCode($code);

            Event::dispatch(new AccountAuthSuccessEvent(account: $this->getUser()->getAccount(),description: "安全码验证成功 code:{$code}"));

        } catch (VerificationCodeIncorrect $e) {

            Event::dispatch(new AccountAuthFailEvent(account: $this->getUser()->getAccount(),description: "安全码验证失败 {$e->getMessage()}"));
            throw $e;
        }
    }
}
