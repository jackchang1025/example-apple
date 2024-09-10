<?php

namespace App\Apple\Service;

use App\Apple\Service\Client\AppleIdClient;
use App\Apple\Service\Client\BaseClient;
use App\Apple\Service\Client\IdmsaClient;
use App\Apple\Service\Client\PhoneCodeClient;
use App\Apple\Service\Client\AuthClient;
use App\Apple\Service\Client\Response;
use App\Apple\Service\Exception\UnauthorizedException;
use App\Apple\Service\Exception\VerificationCodeIncorrect;
use App\Apple\Service\User\Config;
use App\Apple\Service\User\User;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\Utils;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Apple 类
 *
 * 这个类作为 Apple 服务的统一入口点，管理并提供对不同 HTTP 客户端的访问。
 * 它聚合了 IdmsaClient、AppleIdClient 和 PhoneCodeClient，允许通过简单的属性访问来使用这些客户端的方法。
 * @property-read  IdmsaClient $idmsa 访问 IDMSA 服务的客户端
 * @property-read  AppleIdClient $appleId 访问 Apple ID 服务的客户端
 * @property-read  PhoneCodeClient $phoneCode 访问 手机验证码服务的客户端
 */
class Apple
{
    /**
     * @var array 存储所有客户端实例的数组
     */
    private array $clients;

    /**
     * Apple 类的构造函数
     *
     * @param IdmsaClient $idmsaClient IDMSA 服务的客户端
     * @param AppleIdClient $appleIdClient Apple ID 服务的客户端
     * @param PhoneCodeClient $phoneCodeClient 手机验证码服务的客户端
     */
    public function __construct(
        private readonly IdmsaClient $idmsaClient,
        private readonly AppleIdClient $appleIdClient,
        private readonly PhoneCodeClient $phoneCodeClient,
        private readonly AuthClient $authClient,
        private readonly User $user,
        private readonly CookieJarInterface $cookieJar,
        private readonly LoggerInterface $logger,
    ) {
        $this->clients = [
            'idmsa'     => $this->idmsaClient,
            'appleId'   => $this->appleIdClient,
            'phoneCode' => $this->phoneCodeClient,
        ];
    }

    public function getCookieJar(): CookieJarInterface
    {
        return $this->cookieJar;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function clear(): void
    {
        $this->user->clear();

        $this->cookieJar->clear();
    }

    /**
     * 魔术方法，用于动态访问不同的客户端
     *
     * @param string $name 客户端的名称（'idmsa', 'appleId', 或 'phoneCode'）
     * @return BaseClient 请求的客户端实例
     * @throws \InvalidArgumentException 如果请求的客户端不存在
     */
    public function __get(string $name): BaseClient
    {
        if (isset($this->clients[$name])) {
            return $this->clients[$name];
        }
        throw new \InvalidArgumentException("客户端 $name 不存在。");
    }
    protected function buildUUid(): string
    {
        return sprintf('auth-%s', uniqid());
    }

    /**
     * @return void
     * @throws ConnectionException
     */
    public function bootstrap()
    {

        /**
         * @var $promise \GuzzleHttp\Promise\Promise
         */
        $promise1 = $this->appleId->getClient()
            ->async()
            ->get('bootstrap/portal')
            ->then(
                function (\Illuminate\Http\Client\Response  $response) {

                },
                function (RequestException $e) {

                    Log::error($e);
                }
            );

        $promise2 = $this->idmsa->getClient()
            ->async()
            ->get('appleauth/auth/authorize/signin',[
                'frame_id'      => $this->buildUUid(),
                'skVersion'     => '7',
                'iframeId'      => $this->buildUUid(),
                'client_id'     => 'af1139274f266b22b68c2a3e7ad932cb3c0bbe854e13a79af78dcc73136882c3',
                'redirect_uri'  => 'https://appleid.apple.com',
                'response_type' => 'code',
                'response_mode' => 'web_message',
                'state'         => $this->buildUUid(),
                'authVersion'   => 'latest',
            ])->then(
                function (\Illuminate\Http\Client\Response  $response) {

                },
                function (RequestException $e) {

                    Log::error($e);
                }
            );;

        $promises = [
            'bootstrap' => $promise1,
            'auth' => $promise2,
        ];

        // This will truly execute both promises concurrently
        $results = Utils::settle($promises)->wait();
//        dd($response);
//        $response = $this->appleId->bootstrap();
//        if (empty($data = $response->json())) {
//            throw new UnauthorizedException('未获取到配置信息');
//        }
//
//        $this->user->setConfig($this->createConfig($data));
//
//        $this->idmsa->authAuthorizeSignin();

//        return $response;
    }

    /**
     * @return Response
     * @throws GuzzleException|UnauthorizedException|ConnectionException
     */
    public function auth(): Response
    {
        $response = $this->idmsa->auth();

        $data = $response->getJsonData();
        if (empty($data['direct'])){
            throw new UnauthorizedException('未获取到授权信息');
        }

        $this->logger->info('授权信息', $data);

        $this->user->setPhoneInfo($response->getTrustedPhoneNumbers()->toArray());
        return $response;
    }


    /**
     * @param string $account
     * @param string $password
     * @return Response
     * @throws GuzzleException
     * @throws UnauthorizedException|ConnectionException
     * @throws RequestException
     */
    public function signin(string $account, string $password): Response
    {
        $initResponse = $this->authClient->init($account);
        if (empty($initResponse->json('key'))){
            throw new InvalidArgumentException("key IS EMPTY");
        }
        if (empty($initResponse->json('value'))){
            throw new InvalidArgumentException("value IS EMPTY");
        }

        $signinInitResponse = $this->idmsaClient->signinInit(a: $initResponse->json('value'), account: $account);
        if (empty($signinInitResponse->json('salt'))){
            throw new InvalidArgumentException("salt IS EMPTY");
        }
        if (empty($signinInitResponse->json('b'))){
            throw new InvalidArgumentException("b IS EMPTY");
        }
        if (empty($signinInitResponse->json('c'))){
            throw new InvalidArgumentException("c IS EMPTY");
        }
        if (empty($signinInitResponse->json('iteration'))){
            throw new InvalidArgumentException("iteration IS EMPTY");
        }
        if (empty($signinInitResponse->json('protocol'))){
            throw new InvalidArgumentException("protocol IS EMPTY");
        }

        $completeResponse = $this->authClient->complete(
            key: $initResponse->json('key'),
            salt: $signinInitResponse->json('salt'),
            b: $signinInitResponse->json('b'),
            c: $signinInitResponse->json('c'),
            password: $password,
            iteration: $signinInitResponse->json('iteration'),
            protocol: $signinInitResponse->json('protocol')
        );
        if (empty($completeResponse->json('M1'))){
            throw new InvalidArgumentException("M1 IS EMPTY");
        }
        if (empty($completeResponse->json('M2'))){
            throw new InvalidArgumentException("M2 IS EMPTY");
        }
        if (empty($completeResponse->json('c'))){
            throw new InvalidArgumentException("c IS EMPTY");
        }

        $this->idmsaClient->complete(
            account: $account,
            m1: $completeResponse->json('M1'),
            m2: $completeResponse->json('M2'),
            c: $completeResponse->json('c'),
        );

        return $this->auth();
    }

    /**
     * @param string $account
     * @param string $password
     * @return Response
     * @throws ConnectionException
     * @throws GuzzleException
     * @throws RequestException
     * @throws UnauthorizedException
     */
    public function login(string $account, string $password): Response
    {
        $this->idmsa->login($account, $password);

        return $this->auth();
    }

    /**
     * @param string $code
     * @return Response
     * @throws UnauthorizedException|VerificationCodeIncorrect|ConnectionException
     */
    public function validateSecurityCode(string $code): Response
    {
        $response = $this->idmsa->validateSecurityCode($code);

        if ($response->status() === 412){

            $this->managePrivacyAccept();
        }else if ($response->status() === 400) {

            throw new VerificationCodeIncorrect($response->service_errors_first()?->getMessage(), $response->status());
        } else if (!in_array($response->status(), [204, 200])) {

            throw new UnauthorizedException($response->service_errors_first()?->getMessage(), $response->status());
        }

        return $response;
    }

    /**
     * @return void
     * @throws ConnectionException
     */
    protected function managePrivacyAccept(): void
    {
        $this->idmsa->appleAuthRepairComplete();
    }

    /**
     * @param string $code
     * @param int $id
     * @return Response
     * @throws VerificationCodeIncorrect|UnauthorizedException|ConnectionException
     */
    public function validatePhoneSecurityCode(string $code, int $id = 1): Response
    {
        // 验证手机号码
        $response = $this->idmsa->validatePhoneSecurityCode($code,$id);

        if ($response->status() === 412){

            $this->managePrivacyAccept();
        }else if ($response->status() === 400) {

            throw new VerificationCodeIncorrect($response->getFirstErrorMessage(), $response->status());
        } else if (!in_array($response->status(), [204, 200])) {

            throw new UnauthorizedException($response->getFirstErrorMessage(), $response->status());
        }

        return $response;
    }

    /**
     * 发送手机验证码
     * @param int $ID
     * @return Response
     * @throws ConnectionException
     */
    public function sendPhoneSecurityCode(int $ID): Response
    {
        return $this->idmsa->sendPhoneSecurityCode($ID);
    }

    protected function createConfig(array $config = []): Config
    {
        return new Config(
            $config['apiUrl'] ?? '',
            $config['serviceKey'] ?? '',
            $config['serviceUrl'] ?? '',
            $config['environment'] ?? '',
            $config['timeOutInterval'] ?? '',
            $config['moduleTimeOutInSeconds'] ?? 0,
            $config['$XAppleIDSessionId'] ?? null,
            $config['pageFeatures'] ?? [],
            $config['signoutUrls'] ?? []
        );
    }
}
