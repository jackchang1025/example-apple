<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Integrations\AppleId;

use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\Cookies\CookieAuthenticator;
use Modules\AppleClient\Service\Header\HeaderSynchronizeInterface;
use Modules\AppleClient\Service\Integrations\AppleConnector;
use Modules\AppleClient\Service\Integrations\AppleId\Resources\AccountManagerResource;
use Modules\AppleClient\Service\Integrations\AppleId\Resources\AuthenticateResources;
use Modules\AppleClient\Service\Integrations\AppleId\Resources\BootstrapResources;
use Modules\AppleClient\Service\Integrations\AppleId\Resources\PaymentResources;
use Modules\AppleClient\Service\Integrations\AppleId\Resources\SecurityDevicesResources;
use Modules\AppleClient\Service\Integrations\AppleId\Resources\SecurityPhoneResources;

class AppleIdConnector extends AppleConnector
{

    public function __construct(
        protected Apple $apple,
        CookieAuthenticator $authenticator,
        HeaderSynchronizeInterface $headerSynchronize
    ) {

        parent::__construct($apple, $authenticator, $headerSynchronize);

        $this->authenticator = $authenticator;
    }

    public function resolveBaseUrl(): string
    {
        return 'https://appleid.apple.com';
    }

    public function defaultPersistentHeaders(): array
    {
        return ['scnt'];
    }

    protected function defaultHeaders(): array
    {
        return [
            'Connection' => 'Keep-Alive',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json, text/plain, */*',
            // 'Accept-Language' => 'zh-CN,en;q=0.9,zh;q=0.8',
            'X-Apple-I-Request-Context' => 'ca',
            // 'X-Apple-I-TimeZone' => 'Asia/Shanghai',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Dest' => 'empty',
            'referer' => $this->resolveBaseUrl(),
            'host' => 'appleid.apple.com',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            'x-apple-i-fd-client-info' => [
                "U" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36",
                "L" => 'en_US',
                "Z" => "GMT+02:00",
                "V" => "1.1",
                "F" => "",
            ],
        ];
    }

    public function getPaymentResources(): PaymentResources
    {
        return new PaymentResources($this);
    }

    public function getAuthenticateResources(): AuthenticateResources
    {
        return new AuthenticateResources($this);
    }

    public function getBootstrapResources(): BootstrapResources
    {
        return new BootstrapResources($this);
    }

    public function getSecurityDevicesResources(): SecurityDevicesResources
    {
        return new SecurityDevicesResources($this);
    }

    public function getSecurityPhoneResources(): SecurityPhoneResources
    {
        return new SecurityPhoneResources($this);
    }

    public function getAccountManagerResources(): AccountManagerResource
    {
        return new AccountManagerResource($this);
    }
}
