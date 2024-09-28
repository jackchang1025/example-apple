<?php

namespace App\Console\Commands;

use App\Apple\Selenium\AppleClient\AccountManageRequest;
use App\Apple\Selenium\AppleClient\AppleConnector;
use App\Apple\Selenium\AppleClient\AppleRequest;
use App\Apple\Selenium\AppleClient\SignINRequest;
use App\Apple\Service\Cookies\CacheCookie;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use \InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Panther\Client;

class AppleClienCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apple:login';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected static string $accountName = 'licade_2015@163.com';

    protected static string $password = 'AtA3FH2sBfrtSv6s';

    protected Client $client;

    protected function getSavePath (string $path): string
    {
        return storage_path($path);
    }

    /**
     * Execute the console command.
     */
    public function handle(CacheInterface $cache,LoggerInterface $logger): void
    {
        $seleniumHub = 'http://selenium:4444/wd/hub';
        try {

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

            // 等待页面加载完成（可以根据实际情况替换为具体的等待
//
//            //设置 cookie 信息
//            $chromeOptions->addArguments(['--user-data-dir=/tmp/selenium/chrome']);
//
//            $capabilities = DesiredCapabilities::chrome();
//            $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

            // 创建 Symfony Panther 客户端，连接到远程 Selenium WebDriver
            $this->client = Client::createSeleniumClient(host:$seleniumHub,options: [
                'timeout' => 300,           // 总超时时间：300 秒
                'request_timeout' => 300,   // 请求超时时间：300 秒
                'capabilities' => $options->toCapabilities(),
            ]);

            // 访问父域名
            $this->client->request('GET', 'https://apple.com');
            // 等待页面加载完成（可以根据实际情况替换为具体的等待条件）
            sleep(8); // 简单等待 5 秒，建议使用显式等待
            $this->client->takeScreenshot($this->getSavePath('home.apple.png'));


            // 访问 Apple 账户登录页面
            $this->client->request('GET', 'https://account.apple.com/sign-in');
            $this->client->takeScreenshot($this->getSavePath('screenshot1.png'));


            // 等待 iframe 出现（最多等待 30 秒）
            $this->client->waitFor('iframe#aid-auth-widget-iFrame', 30);
            $iframe = $this->client->findElement(WebDriverBy::id('aid-auth-widget-iFrame'));

            // 切换到 iframe 上下文
            $this->client->getWebDriver()->switchTo()->frame($iframe);

            sleep(3);
            // 截取主页截图
            $this->client->takeScreenshot($this->getSavePath('after.signin.apple.png'));


            // 等待账户名输入字段出现（最多等待 10 秒）
            $this->client->waitFor('#account_name_text_field', 10);


            // 在账户名输入字段中输入账号
            $accountNameField = $this->client->findElement(WebDriverBy::id('account_name_text_field'));
            $accountNameField->sendKeys(self::$accountName);

            // 验证账号输入是否成功
            $enteredEmail = $accountNameField->getAttribute('value');
            if ($enteredEmail !== self::$accountName) {
                throw new \RuntimeException("账号输入失败，预期: admin@qq.com，实际: {$enteredEmail}");
            }

            echo "账号输入验证通过。\n";

            sleep(1);
            $this->client->takeScreenshot($this->getSavePath('input.account.signin.apple.png'));

            // 点击 "sign-in" 按钮（使用 WebDriverBy::id 定位）
            $this->client->waitFor('#sign-in', 10);
            $signInButton = $this->client->findElement(WebDriverBy::id('sign-in'));

            // 检查元素是否可见且未禁用
            if($signInButton->isDisplayed() && $signInButton->getAttribute('disabled') === 'true'){
                throw new \RuntimeException("signInButton disabled is {$signInButton->getAttribute('disabled')}");
            }

            $signInButton->click();
            sleep(1);
            $this->client->takeScreenshot($this->getSavePath('click.button.account.signin.apple.png'));

            // 等待密码输入字段出现（最多等待 10 秒）
            $passwordField = $this->client->waitFor('#password_text_field', 10);
            $accountNameField = $this->client->findElement(WebDriverBy::id('password_text_field'));

            // 点击输入框以设置焦点
            $accountNameField->click();
            echo "已点击输入框: 'password_text_field'\n";
            sleep(1);
            $this->client->takeScreenshot($this->getSavePath('click.password.signin.apple.png'));
            // 清除输入框中的现有内容
            $accountNameField->clear();
            echo "已清除输入框中的现有内容。\n";
            sleep(1);
            $this->client->takeScreenshot($this->getSavePath('clear.password.signin.apple.png'));
            // 在密码输入字段中输入密码
            $accountNameField->sendKeys(self::$password);
            echo "已在 password_text_field 输入文本: '123456'\n";


            // 验证密码输入是否成功
            $enteredPassword = $passwordField->getAttribute('value');
            // 通常密码字段不会返回实际输入的密码出于安全原因，可能需要其他方式验证
            // 比如检查是否有错误提示
            echo "密码输入操作完成（无法直接验证密码内容）。{$enteredPassword} \n";

            sleep(1);
            $this->client->takeScreenshot($this->getSavePath('input.password.signin.apple.png'));
            // 再次点击 "sign-in" 按钮以登录（使用 WebDriverBy::id 定位）
            $this->client->waitFor('#sign-in', 10);


            $signInButton = $this->client->findElement(WebDriverBy::id('sign-in'));
            // 检查元素是否可见且未禁用
            if($signInButton->isDisplayed() && $signInButton->getAttribute('disabled') === 'true'){
                throw new \RuntimeException("signInButton disabled is {$signInButton->getAttribute('disabled')}");
            }

            $signInButton->click();
            sleep(1);
            $this->client->takeScreenshot($this->getSavePath('click.button.password.signin.apple.png'));


            // // 等待 10 秒后截取登录成功后的页面截图
            sleep(5);

            // // 截取登录成功后的页面截图
            $this->client->takeScreenshot($this->getSavePath('signin.apple.png'));
            echo "所有截图已成功生成。\n";


            // 获取并存储 Cookie
            $cookieArray = [];
            $cookies = $this->client->getWebDriver()->manage()->getCookies();
            foreach ($cookies as $cookie) {
                $cookieArray[] = $cookie->toArray();
            }

            Cache::put(self::$accountName, $cookieArray, now()->addMinutes(30));
            echo "已获取并存储用户的 Cookie 信息。\n";
            var_dump(json_encode($cookieArray, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            $this->client->waitFor('.form-security-code-inputs', 10);

            $this->maxAuth();

            // 等待验证结果页面加载
            sleep(10);
            $this->client->takeScreenshot($this->getSavePath('verification.success.png'));
            echo "验证码验证成功，截图已保存。\n";

            // 获取并存储 Cookie
            $cookieArray = [];
            $cookies = $this->client->getWebDriver()->manage()->getCookies();
            foreach ($cookies as $cookie) {
                $cookieArray[] = $cookie->toArray();
            }

            Cache::put(self::$accountName, $cookieArray, now()->addMinutes(9999));
            echo "已获取并存储用户的 Cookie 信息。\n";
            var_dump(json_encode($cookieArray, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));


            // 访问 Apple 账户登录页面
            $this->client->request('GET', 'https://account.apple.com/account/manage');

            sleep(10);
            $this->client->takeScreenshot($this->getSavePath('account.manager.apple.png'));


            // 等待 iframe 出现（最多等待 30 秒）
            $this->client->waitFor('.page-title',10);

            // 获取并存储 Cookie
            $cookieArray = [];
            $cookies = $this->client->getWebDriver()->manage()->getCookies();
            foreach ($cookies as $cookie) {
                $cookieArray[] = $cookie->toArray();
            }

            Cache::put(self::$accountName, $cookieArray, now()->addMinutes(9999));
            echo "已获取并存储用户的 Cookie 信息。\n";
            var_dump(json_encode($cookieArray, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));


        } catch (\Exception $e) {

            var_dump($e);
        }

    }

    public function maxAuth(): bool
    {
        for ($i = 0; $i < 10; $i++) {

            try {

                return $this->auth();
            } catch (\InvalidArgumentException $e) {

                $this->error("{$e->getMessage()}，重新获取验证码");
            }
        }

        throw new \RuntimeException('验证码验证失败');
    }

    /**
     * @return true
     */
    protected function auth(): bool
    {
        $code = $this->getCode();

        echo "您的验证码是：{$code}。开始授权\n";

        // 找到所有的验证码输入框
        $inputFields = $this->client->findElements(WebDriverBy::cssSelector('.form-security-code-input'));
        // 确保找到了 6 个输入框
        if (count($inputFields) !== 6) {
            throw new \RuntimeException("Expected 6 input fields, but found " . count($inputFields));
        }

        // 分割验证码并输入到各个输入框
        foreach (str_split($code) as $index => $digit){

            $inputField = $inputFields[$index];

            // 点击输入框以设置焦点
            $inputField->click();
            echo "已点击第 " . ($index + 1) . " 个输入框\n";

            // 清除输入框中的现有内容
            $inputField->clear();
            echo "已清除第 " . ($index + 1) . " 个输入框中的现有内容\n";

            // 输入验证码数字
            $inputField->sendKeys($digit);
            echo "已在第 " . ($index + 1) . " 个输入框中输入: " . $digit . "\n";

            // 短暂等待，模拟人工输入
            usleep(200000); // 等待 0.2 秒

            $this->client->takeScreenshot($this->getSavePath("input.code.digit" . ($index + 1) . ".png"));
        }

        //判断验证码是否正确
        sleep(5);
        //获取 .form-message-wrapper 下的 .form-message 元素

        try {
            $messageElement = $this->client->findElement(WebDriverBy::cssSelector('.form-message-wrapper .form-message'));
            $errorMessage = $messageElement->getText();

            if (!empty($errorMessage)) {
                $this->client->takeScreenshot($this->getSavePath("post.verification.error.png"));
                throw new InvalidArgumentException("验证码错误: $errorMessage");
            }
        } catch (NoSuchElementException $e) {
            // 如果没有找到错误消息元素，可能意味着验证成功
            $this->info("没有发现错误消息，验证可能成功。");
        }
        return true;
    }

    public function getCode() {

        $code = $this->ask('What is your code?');

        try {
            $this->validateCodeInput($code);
        } catch (\Exception|\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return $this->getCode();
        }

        return $code;
    }

    protected function validateCodeInput(string $code): string
    {
        // 确保验证码为6位数字
        if (!preg_match('/^\d{6}$/', $code)) {
            throw new \InvalidArgumentException("验证码格式错误，请输入6位数字。");
        }
        return $code;
    }
}
