<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service;

use Saloon\Traits\Conditionable;
use Modules\AppleClient\Service\Config\HasConfig;
use Modules\AppleClient\Service\Cookies\CookieJarInterface;
use Modules\AppleClient\Service\Cookies\HasCookie;
use Modules\AppleClient\Service\Header\HasHeaderSynchronize;
use Modules\AppleClient\Service\Helpers\Helpers;
use Modules\AppleClient\Service\Integrations\AppleAuth\AppleAuthConnector;
use Modules\AppleClient\Service\Integrations\AppleId\AppleIdConnector;
use Modules\AppleClient\Service\Integrations\Idmsa\IdmsaConnector;
use Modules\AppleClient\Service\Logger\Logger;
use Modules\AppleClient\Service\Proxy\HasProxy;
use Psr\Log\LoggerInterface;
use Saloon\Contracts\ArrayStore as ArrayStoreContract;
use Saloon\Traits\Macroable;
use Modules\AppleClient\Service\Store\CacheStore;
use Modules\AppleClient\Service\Store\HasCacheStore;
use Modules\AppleClient\Service\Trait\HasTries;

class AppleClient
{
    use Macroable;
    use AppleId;
    use Idmsa;
    use AppleAuth;
    use HasConfig;
    use HasProxy;
    use HasCookie;
    use HasHeaderSynchronize;
    use Helpers;
    use Logger;
    use Conditionable;
    use HasCacheStore;
    use HasTries;

    protected AppleIdConnector $appleIdConnector;
    protected IdmsaConnector $idmsaConnector;
    protected AppleAuthConnector $appleAuthConnector;


    /**
     * @param ArrayStoreContract $config
     * @param ArrayStoreContract|null $headerRepositories
     * @param CookieJarInterface|null $cookieJar
     * @param LoggerInterface|null $logger
     * @param CacheStore|null $cacheStore
     */
    public function __construct(
        ArrayStoreContract $config,
        ?ArrayStoreContract $headerRepositories = null,
        ?CookieJarInterface $cookieJar = null,
        ?LoggerInterface $logger = null,
        ?CacheStore $cacheStore = null,
    ) {
        $this->withConfig($config);
        $this->withCacheStore($cacheStore);
        $this->setLogger($logger);
        $this->setHeaderRepositories($headerRepositories);
        $this->setCookieJar($cookieJar);

        $this->appleIdConnector = new AppleIdConnector($this);
        $this->idmsaConnector = new IdmsaConnector($this);
        $this->appleAuthConnector = new AppleAuthConnector($this);
    }

    public function getAppleIdConnector(): AppleIdConnector
    {
        return $this->appleIdConnector;
    }

    public function getIdmsaConnector(): IdmsaConnector
    {
        return $this->idmsaConnector;
    }

    public function getAppleAuthConnector(): AppleAuthConnector
    {
        return $this->appleAuthConnector;
    }

    /**
     * @param string $account
     * @param string $password
     *
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     * @throws \JsonException
     *
     * @return Response\Response
     */
    public function authLogin(string $account, string $password): Response\Response
    {
        $initResponse = $this->getAppleAuthConnector()->appleAuthInit($account);

        $signinInitResponse = $this->getIdmsaConnector()->init(a: $initResponse->json('value'), account: $account);

        $completeResponse = $this->getAppleAuthConnector()->appleAuthComplete(
            key: $initResponse->json('key'),
            salt: $signinInitResponse->json('salt'),
            b: $signinInitResponse->json('b'),
            c: $signinInitResponse->json('c'),
            password: $password,
            iteration: $signinInitResponse->json('iteration'),
            protocol: $signinInitResponse->json('protocol')
        );

        return $this->getIdmsaConnector()->complete(
            account: $account,
            m1: $completeResponse->json('M1'),
            m2: $completeResponse->json('M2'),
            c: $completeResponse->json('c'),
        );
    }
}
