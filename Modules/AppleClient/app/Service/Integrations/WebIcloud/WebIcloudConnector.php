<?php

namespace Modules\AppleClient\Service\Integrations\WebIcloud;

use Illuminate\Support\Facades\Log;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\Cookies\CookieAuthenticator;
use Modules\AppleClient\Service\Header\HeaderSynchronizeInterface;
use Modules\AppleClient\Service\Integrations\AppleConnector;
use Modules\AppleClient\Service\Integrations\WebIcloud\Resources\AuthenticateResources;
use phpDocumentor\Reflection\Types\Self_;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

class WebIcloudConnector extends AppleConnector
{
    public function __construct(
        protected Apple $apple,
        CookieAuthenticator $authenticator,
        HeaderSynchronizeInterface $headerSynchronize
    ) {

        parent::__construct($apple, $authenticator, $headerSynchronize);

        $this->authenticator = $authenticator;
    }

    public function defaultHeaders(): array
    {
        return [
            'Host'                 => $this->getHost(),
            'Accept-Encoding'      => 'gzip',
            'Referer'              => $this->originAndReferer(),
            'Origin'               => $this->originAndReferer(),
            'X-Requested-With'     => 'XMLHttpRequest',
            'User-Agent'           => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10) AppleWebKit/600.1.3 (KHTML, like Gecko)',
            'Proxy-Connection'     => 'keep-alive',
            'X-MMe-Client-Info'    => '<MacBook Pro> <Mac OS X;10.10.0;14A314h> <webclient/731eb0905570 (com.apple.systempreferences/14.0)>',
            'Connection'           => 'keep-alive',
            'X-SproutCore-Version' => '1.6.0',
            'Accept-Language'      => 'zh-cn',
            'user-agent'           => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'sec-ch-ua'            => 'Google Chrome;v="131", "Chromium";v="131", "Not_A Brand";v="24"',
            'sec-ch-ua-mobile'     => '?0',
            'sec-ch-ua-platform'   => '"Windows"',
            'sec-fetch-dest'       => 'empty',
            'sec-fetch-mode'       => 'cors',
            'sec-fetch-site'       => 'same-site',
        ];
    }

    public function getHost(): string
    {
        // 大陆账号
        if ($this->apple->getAccount()->isCN()) {
            return 'setup.icloud.com.cn';
        }

        // 非大陆账号
        return 'setup.icloud.com';
    }

    public function originAndReferer(): string
    {
        // 大陆账号
        if ($this->apple->getAccount()->isCN()) {
            return 'https://www.icloud.com.cn';
        }

        // 非大陆账号
        return 'https://www.icloud.com';
    }

    public function resolveBaseUrl(): string
    {
        // 大陆账号
        if ($this->apple->getAccount()->isCN()) {
            return 'https://setup.icloud.com.cn';
        }

        // 非大陆账号
        return 'https://setup.icloud.com';
    }


    public function getAuthenticateResources(): AuthenticateResources
    {
        return new AuthenticateResources($this);
    }
}
