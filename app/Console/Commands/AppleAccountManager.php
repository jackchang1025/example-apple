<?php

namespace App\Console\Commands;

use App\Apple\Selenium\AppleClient\AccountManageRequest;
use App\Apple\Selenium\AppleClient\AppleConnector;
use App\Apple\Selenium\AppleClient\AppleRequest;
use App\Apple\Service\Cookies\CacheCookie;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Cookie;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Panther\Client;

class AppleAccountManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apple:account-manager';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected static string $accountName = 'licade_2015@163.com';

    protected static string $password = 'AtA3FH2sBfrtSv6s';
    protected Client $client;


    /**
     * Execute the console command.
     */
    public function handle(CacheInterface $cache,LoggerInterface $logger)
    {
        $seleniumHub = 'http://selenium:4444/wd/hub';

        //设置cookie 信息
        $cookies = new CacheCookie(clientId:'6864113cf3b479d2bca673670895a2cf99d70a54',cache: $cache,logger: $logger);

//        dd($cookies->toArray());

        // 创建 ChromeOptions 实例
            $options = new ChromeOptions();

            // 添加必要的启动参数
            $options->addArguments([
                '--no-sandbox',
                '--disable-gpu',
                '--disable-dev-shm-usage',
                '--incognito',
                '--start-maximized',
            ]);

            // 创建 Symfony Panther 客户端，连接到远程 Selenium WebDriver
            $this->client = Client::createSeleniumClient(host:$seleniumHub,options: [
                'timeout' => 300,           // 总超时时间：300 秒
                'request_timeout' => 300,   // 请求超时时间：300 秒
                'capabilities' => $options->toCapabilities(),
            ]);

        $this->client->request('GET', 'https://apple.com');
        $this->client->takeScreenshot(storage_path('home.apple.png'));

        /**
         * @var RemoteWebDriver $webDriver
         */
        $webDriver = $this->client->getWebDriver();

        Cache::put('session_id', $this->client, now()->addMinutes(30));

        dd($webDriver->getSessionID());

        // 设置 idmsa.apple.com 的 cookie
        $this->client->request('GET', 'https://idmsa.apple.com');
        $this->client->takeScreenshot(storage_path('idmsa.apple.png'));



        $this->client->executeScript("document.cookie = 'aasp=93E64D3C680C0870497203D2F795A8DD1EDAB4149DBE06A9140BAC5D7C3452C222EAC5582EAC2469FF274043E6B9584714FCFE3B1BB18BC1751260325A5296FA461E767B83914F025CC52BA8C1A6CD58C75B3A62A4BFE472BAB72D1DD1B6FB9930361CD8D58A6A81F0056914E73B1F39AA52F5908B4FAECB; domain=.idmsa.apple.com; path=/;'");
        $this->client->executeScript("document.cookie = 'myacinfo=DAWTKNV323952cf8084a204fb20ab2508441a07d02d3bfd1a88977346d0818443bb6ac966a8a3b567b5651061d9dd796642bff6b9bb8ce8fc99daacd8a292940190d5e383d3c583f46f42e331cd1fb4f88a95db669085fcc2955894c4b2995a9034d47ecffe5e98dcdc3ede749b5f58f6ad2fdcf17fcd53b2601776574b64cd39994d91c6488b64c583f26ade6dddc2624e41aa12fc82efa5e51dcf1474eb6694df47d6067fcb9952134b23818fe1e460c94e683664edd7396cbea59edc6f91f2ca9b6fab3827a3e0afda7621fd5350981bc06b28aa786ca256dc71431661059e340d40bf7f09965bede1be5861b77efd85c456ca82fbad7b93aa785a9a816dd06c9f9dff9043cdde48e5a7d28f31e3c85575675b759994e908bf87105b0620158f17f41b892c2f9c2950534139404a0aee14166a2212785fdccd95c7183f6fe0292cd44b9c573f89d3c2c72f2008fa8827369829eb9f34fb6e91ba8dc77b5ad46a299f2083920673f3d9aaf25fe12dbd240ebc49c23acdb938c5595ffbbb7dfa51ada01365f14b7b83b3bba0ba1afa36484cf567b4740f6042acbc1fce44ee9632b2e23f741e4d2d8ae7ed5ed450916f281532fc0dbaf69a16fdd8f4394e0f96078d7bff9c535000e50e13cb831c48bb8cd68ebced618d0cd91dcb1373fd0756e44fcdc476d53104c2652a1d2fe89d4f82c55a9134e35ec60a8a814f27f39f8236cff44a169f3000482ba56fc50a7b9a0b422f6a83ce469efb5e1baeea8303704810e39c04b520b5c800434ef5ca312f71bdea1414fbb0a48ab5a27bcbf01abcc7debbbcfecbfa4235ae08aade492410f80682fbd5182229c10c7c6d861f36cad9ec5bd69fadf7596ecd6498d2708775090482f7145585a47V3; domain=.apple.com; path=/;'");

//        $cookies = array_map(function (array $cookie) {
//
//            $cookie = array_change_key_case($cookie);
//
////            $cookie['domain'] = $this->normalizeDomain($cookie['domain']);
//            $cookie['domain'] = ".".$cookie['domain'];
//            $cookie['httpOnly'] = (bool) $cookie['httponly'];
//            $cookie['sameSite'] = $cookie['samesite'] ?? 'Lax';
//
//            $this->client->manage()->addCookie(Cookie::createFromArray($cookie));
//            return Cookie::createFromArray($cookie);
//
//        },$cookies->toArray());

//        $this->client->request('GET', 'https://apple.com');
//        $this->client->takeScreenshot(base_path('home2.apple.png'));
//        return;

        $this->client->request('GET', 'https://account.apple.com/account/manage');
        sleep(5);
        $this->client->takeScreenshot(storage_path('account.apple.png'));

        // 获取并存储 Cookie
        $cookieArray = [];
        $cookies = $this->client->getWebDriver()->manage()->getCookies();
        foreach ($cookies as $cookie) {
            $cookieArray[] = $cookie->toArray();
        }

        var_dump($cookieArray);

        return;

        $appleConnector = new AppleConnector(account: self::$accountName, password: self::$password);
        $appleRequest = new AppleRequest();
        $appleConnector->send($appleRequest);

        $cookieJar = $appleConnector->createFromArray($cookies->toArray());
        var_dump($cookies->toArray());


        //访问账号管理页面
        $request = new AccountManageRequest(phoneNumber: '13067772321', password: 'AtA3FH2sBfrtSv6');
        $appleConnector->send($request);

        // 获取并存储 Cookie
        $cookieArray = [];
        $cookies = $appleConnector->client()->getWebDriver()->manage()->getCookies();
        foreach ($cookies as $cookie) {
            $cookieArray[] = $cookie->toArray();
        }
        echo "已获取并存储用户的 Cookie 信息。\n";
        var_dump(json_encode($cookieArray, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));


//        $cookies = Cache::get(self::$accountName);
//        var_dump($cookies);
//        echo "已获取用户的 Cookie 信息。\n";
//
//
//
//


    }
}
