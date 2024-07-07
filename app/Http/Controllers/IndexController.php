<?php

namespace App\Http\Controllers;

use App\Apple\Service\Exception\UnauthorizedException;
use App\Apple\Service\HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndexController extends Controller
{

    protected HttpClient $http;

    public function __construct(protected readonly Request $request, protected readonly Container $container)
    {
        $this->http = $container->make(
            HttpClient::class,
            ['clientId' => $this->request->cookie('laravel_session', uuid_create(UUID_TYPE_TIME))]
        );
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

        $accountName = $this->request->input('accountName');
        $password    = $this->request->input('password');

        if (empty($accountName) || empty($password)) {
            return $this->error('账号或密码不能为空');
        }

        $response = $this->http->signin($accountName, $password);

        return $this->success(data: [
            'Guid' => $this->request->cookie('laravel_session'),
            'Devices' => $response->getDevices(),
            'ID' => $response->getId(),
            'Number' => $response->getNumber(),
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
            return $this->error('apple_verifycode');
        }

        $response = $this->http->authverifytrusteddevicesecuritycode($code);

        return $this->success($response->getData());
    }

    /**
     * 验证手机验证码
     * @return JsonResponse|void
     * @throws GuzzleException
     * @throws UnauthorizedException
     */
    public function smsSecurityCode()
    {
        $Id = $this->request->input('ID');
        $apple_verifycode = $this->request->input('apple_verifycode');

        if (empty($Id) || empty($apple_verifycode)) {
            return $this->redirect();
        }

        $response = $this->http->authVerifyPhoneSecurityCode($apple_verifycode);

        return $this->success($response->getData());
    }

    /**
     * 获取安全码
     * @return JsonResponse
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function SendSecurityCode(): \Illuminate\Http\JsonResponse
    {
        return $this->success($this->http->sendSecurityCode()->getData());
    }

    /**
     * 获取手机号码
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function GetPhone(): JsonResponse
    {
        $response = $this->http->auth();

        $trustedPhoneNumbers = $response->getData('trustedPhoneNumbers');

        return $this->success([
            'trustedPhoneNumbers' => $trustedPhoneNumbers,
            'ID'                  => $trustedPhoneNumbers[0]['id'] ?? 0,
            'Number'              => $trustedPhoneNumbers[0]['obfuscatedNumber'] ?? '',
        ]);
    }

    /**
     * 发送验证码
     * @return JsonResponse
     * @throws GuzzleException
     * @throws UnauthorizedException
     */
    public function SendSms(): JsonResponse
    {
        $ID = (int) $this->request->input('ID');
        if (empty($ID)) {
            return $this->error('ID 不能为空');
        }
        $response = $this->http->sendAuthVerifyPhoneSecurityCode($ID);

        return $this->success($response->getData());
    }

    public function sms()
    {
        return view('index/sms');
    }
}
