<?php

namespace App\Apple\Service;

use App\Apple\Service\Client\AppleIdClient;
use App\Apple\Service\Client\BaseClient;
use App\Apple\Service\Client\IdmsaClient;
use App\Apple\Service\Client\PhoneCodeClient;
use App\Apple\Service\Client\Response;
use App\Apple\Service\Exception\LockedException;
use App\Apple\Service\Exception\UnauthorizedException;
use App\Apple\Service\PhoneCodeParser\PhoneCodeParserInterface;
use App\Apple\Service\User\Config;
use App\Apple\Service\User\User;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Exception\GuzzleException;

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
        private readonly User $user,
        private readonly CookieJarInterface $cookieJar,
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

    /**
     * 获取所有可用的方法
     *
     * 这个方法使用反射来获取所有客户端的公共方法，用于调试和文档生成。
     *
     * @return array 包含每个客户端可用方法的关联数组
     * @throws \ReflectionException
     */
    public function getAvailableMethods(): array
    {
        $methods = [];
        foreach ($this->clients as $clientName => $client) {
            $reflection    = new \ReflectionClass($client);
            $clientMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach ($clientMethods as $method) {
                if (!in_array($method->getName(), ['__construct', '__destruct'])) {
                    $methods[$clientName][] = $method->getName();
                }
            }
        }

        return $methods;
    }

    /**
     * 完成身份验证流程
     *
     * 这个高级方法结合了多个客户端的调用，完成整个身份验证过程。
     * 包括登录、获取手机验证码和验证手机验证码。
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $phoneCodeUrl 获取手机验证码的 URL
     * @param PhoneCodeParserInterface $phoneCodeParser
     * @return array 包含验证结果的关联数组
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function completeAuthentication(
        string $username,
        string $password,
        string $phoneCodeUrl,
        PhoneCodeParserInterface $phoneCodeParser
    ): array {
        // 使用 IDMSA 客户端进行登录
        $signinResponse = $this->idmsa->login($username, $password);
        if ($signinResponse->getStatus() !== 200) {
            return ['success' => false, 'message' => '登录失败'];
        }

        // 使用 PhoneCode 客户端获取手机验证码
        $phoneCodeResponse = $this->phoneCode->getPhoneTokenCode($phoneCodeUrl, $phoneCodeParser);
        if (!$phoneCodeResponse || !isset($phoneCodeResponse->getData()['code'])) {
            return ['success' => false, 'message' => '获取手机验证码失败'];
        }

        $code = $phoneCodeResponse->getData()['code'];

        // 使用 IDMSA 客户端验证手机验证码
        $verifyResponse = $this->idmsa->validatePhoneSecurityCode($code);

        return [
            'success' => $verifyResponse->getStatus() === 200,
            'message' => $verifyResponse->getStatus() === 200 ? '身份验证成功' : '验证失败',
            'data'    => $verifyResponse->getData(),
        ];
    }

    /**
     * @return Response
     * @throws UnauthorizedException|GuzzleException
     */
    public function bootstrap(): Response
    {
        $response = $this->appleId->bootstrap();

        if (empty($data = $response->getData())) {
            throw new UnauthorizedException('未获取到配置信息');
        }

        $this->user->setConfig($this->createConfig($data));

        return $response;
    }

    /**
     * @return Response
     * @throws UnauthorizedException|GuzzleException
     */
    public function auth(): Response
    {
        $response = $this->idmsa->auth();

        if(empty($response->getData())){
            throw new UnauthorizedException('Unauthorized',$response->getStatus());
        }

        $this->user->setPhoneInfo($response->getData());
        return $response;
    }

    /**
     * @param string $accountName
     * @param string $password
     * @return Response
     * @throws GuzzleException
     * @throws UnauthorizedException
     */
    public function signin(string $accountName, string $password): Response
    {
        $this->bootstrap();

        $this->idmsa->authAuthorizeSignin();

        $this->idmsa->login($accountName, $password);

        $authResponse = $this->auth();

        $this->user->set('account', $accountName);
        $this->user->set('password', $password);

        return $authResponse;
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
