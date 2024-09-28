<?php

namespace App\Selenium\Trait;

use App\Selenium\SeleniumManager;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Symfony\Component\Panther\Client;

trait HasSender
{
    protected Client $client;

    protected array $options = [];

    public function client(): Client
    {
        return $this->client ??= $this->createClient();
    }

    public function withClient(Client $client): static
    {
        $this->client = $client;
        return $this;
    }

    public function createClient(): Client
    {
        $chromeOptions = new ChromeOptions();

        // 添加必要的启动参数
        $chromeOptions->addArguments([
            '--no-sandbox',//禁用 Chrome 的沙箱安全特性。这通常在 Docker 容器或某些 CI/CD 环境中使用，因为这些环境可能没有必要的权限来运行沙箱。但请注意，这可能会降低安全性。
            '--disable-gpu',//禁用 GPU 硬件加速。这在没有 GPU 的环境（如某些服务器或 Docker 容器）中很有用，可以避免与图形相关的问题。
            '--disable-dev-shm-usage',//禁用 /dev/shm 的使用，而使用 /tmp 来存储临时文件。这在某些 Linux 系统上很有用，特别是当 /dev/shm 分区太小时。
            // '--incognito',//：启动浏览器的隐身模式。这意味着浏览器不会保存任何浏览历史、Cookie 或其他会话数据。
            '--start-maximized',//启动时最大化浏览器窗口。这确保了在不同环境中有一致的视口大小，有助于提高测试的一致性。
        ]);

//        $perfLoggingPrefs = new \stdClass();
//        $perfLoggingPrefs->enableNetwork = true;
//        $chromeOptions->setExperimentalOption('perfLoggingPrefs', $perfLoggingPrefs);


        $capabilities = DesiredCapabilities::chrome();

//        $loggingPrefs = new \stdClass();
//        $loggingPrefs->performance = 'ALL';
//        $capabilities->setCapability('loggingPrefs', $loggingPrefs);


        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);
        $capabilities->setCapability('goog:loggingPrefs', ['performance' => 'ALL']);

        $browserManager = new SeleniumManager(
            host: 'http://selenium:4444/wd/hub',
            capabilities: $capabilities,
            options: [
                'timeout'         => 300,           // 总超时时间：300 秒
                'request_timeout' => 300,   // 请求超时时间：300 秒
            ],
            sessionId: $this->getSessionNotResetSession()
        );

        return new \Symfony\Component\Panther\Client($browserManager);

        return Client::createSeleniumClient(host: 'http://selenium:4444/wd/hub', options: array_merge($this->getOptions(), $this->defaultOptions()));
    }
    public function setOptions(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    protected function defaultOptions():array
    {
        return [
            'timeout'         => 300,
            'request_timeout' => 300,
        ];
    }
}
