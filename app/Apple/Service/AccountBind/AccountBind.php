<?php

declare(strict_types=1);

namespace App\Apple\Service\AccountBind;

use App\Apple\Service\Trait\ValidateAccount;
use App\Selenium\AppleClient\Page\AccountManage\AccountSecurityPage;
use App\Selenium\AppleClient\Page\AccountManage\AddTrustedPhoneNumbersPage;
use App\Selenium\Exception\PageException;
use App\Apple\Service\Exception\{AttemptBindPhoneCodeException, BindPhoneCodeException, MaxRetryAttemptsException};
use App\Events\AccountBindPhoneFailEvent;
use App\Events\AccountBindPhoneSuccessEvent;
use App\Models\{Account, Phone, User};
use App\Selenium\AppleClient\AppleConnector;
use App\Selenium\AppleClient\Page\AccountManage\ConfirmPasswordPage;
use App\Selenium\AppleClient\Page\AccountManage\ValidateTrustedCodePage;
use App\Selenium\AppleClient\Request\AccountManageRequest;
use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Filament\Notifications\Notification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Psr\Log\LoggerInterface;
use Throwable;

class AccountBind
{
    use PhoneRepository;
    use GetPhoneCodeRepository;
    use ValidateAccount;

    protected array $usedPhoneIds = [];

    protected ?Account $account = null;

    protected ?Phone $phone = null;

    public function __construct(
        protected readonly AppleConnector $connector,
        protected readonly LoggerInterface $logger,
        protected readonly int $maxRetryAttempts = 3,
    ) {
    }

    public function getUsedPhoneIds(): array
    {
        return $this->usedPhoneIds;
    }

    /**
     * @param Account $account
     * @return void
     * @throws AttemptBindPhoneCodeException
     * @throws BindPhoneCodeException
     * @throws ConnectionException
     * @throws Throwable
     */
    public function handle(Account $account): void
    {
        try {

            $this->validateAccount($account);

            $this->account = $account;

            $this->connector->config()
                ->add('screenshot_path',storage_path("/browser/screenshots/{$account->account}"));

            $this->attemptBind();

        } catch (\Throwable  $e) {
            $this->handleException($e);
            throw $e;
        }
    }

    protected function attemptBind(): void
    {
        for ($attempt = 1; $attempt <= $this->maxRetryAttempts; $attempt++) {

            try {

                $this->phone = $this->getAvailablePhone();

                $request = new AccountManageRequest();

                $response = $this->connector->send($request);

                $page = $response->getPage();

                /**
                 * @var AccountSecurityPage $accountSecurityPage
                 */
                $accountSecurityPage = $page->switchToPhoneListPage();

                //点击添加按钮等待模态框弹出
                $addTrustedPhoneNumbersPage = $accountSecurityPage->switchToAddTrustedPhoneNumbersPage();

                //选择手机号码区号
                $page = $addTrustedPhoneNumbersPage->addTrustedPhoneNumbers($this->phone->phone,$this->phone->country_code);

                if ($page instanceof ConfirmPasswordPage) {
                    //等待验证码输入框出现
                    $page->inputConfirmPassword($this->account->password);
                    $page = $page->submit();
                }

                if (!$page instanceof ValidateTrustedCodePage) {
                    throw new BindPhoneCodeException($page,"页面异常");
                }

                // 这里循环获取手机验证码
                $code =  $this->attemptGetPhoneCode($this->phone->phone_address, $this->phone->phoneCodeParser());

                $page->inputTrustedCode($code);

                $accountSecurityPage = $page->submit();

                sleep(2);
                $accountSecurityPage->takeScreenshot('account_security.png');

                $phoneList = $accountSecurityPage->getPhoneList();

                if (!$phoneList->hasMatch($this->phone->phone)) {

                   throw new BindPhoneCodeException($accountSecurityPage,"绑定失败: phone: {$this->phone->phone} phoneList:".json_encode(
                           $phoneList->all(),
                           JSON_THROW_ON_ERROR
                       )
                   );
                }

                // 绑定成功后更新账号状态
                $this->handleBindSuccess();
                return;

            } catch (PageException|AttemptBindPhoneCodeException|TimeoutException|NoSuchElementException $e) {

                if (isset($page)){
                    $page->takeScreenshot("attempt_{$attempt}.png");
                }

                $this->handleBindException(exception: $e, attempt: $attempt);
            }
        }

        throw new MaxRetryAttemptsException(
            sprintf(
                "账号：%s 尝试 %d 次后绑定失败",
                $this->account->account,
                $this->maxRetryAttempts
            )
        );
    }

    /**
     * @return Phone
     * @throws \Throwable
     */
    public function getPhone(): Phone
    {
        return $this->phone;
    }

    /**
     * @return void
     * @throws \Throwable
     */
    protected function handleBindSuccess(): void
    {
        $this->logger->info("Account {$this->account->account} successfully bound to phone number {$this->phone->phone}");

        DB::transaction(function () {
            $this->account->update([
                'bind_phone'         => $this->phone->phone,
                'bind_phone_address' => $this->phone->phone_address,
            ]);
            $this->phone->update(['status' => Phone::STATUS_BOUND]);
        });

        Event::dispatch(
            new AccountBindPhoneSuccessEvent(account: $this->account, description:"账号： {$this->account->account} 绑定成功 手机号码：{$this->phone->phone}")
        );

        Notification::make()
            ->title("账号： {$this->account->account} 绑定成功 手机号码：{$this->phone->phone}")
            ->success()
            ->sendToDatabase(User::get());
    }



    protected function handlePhoneException(Throwable $exception): void
    {
        if (!$this->phone){
            return;
        }

        $status = $exception instanceof BindPhoneCodeException && $exception->getCode() == -28248
            ? Phone::STATUS_INVALID
            : Phone::STATUS_NORMAL;

        Phone::where('id', $this->phone->id)
            ->where('status' ,Phone::STATUS_BINDING)
            ->update(['status' => $status]);

        $this->usedPhoneIds[] = $this->phone->id;
    }

    protected function handleException(\Throwable $e): void
    {
        $this->logger->error("账号： {$this->account?->account} 绑定失败 {$e->getMessage()}");

        $this->handlePhoneException($e);

        $this->account && Event::dispatch(
            new AccountBindPhoneFailEvent(account: $this->account, description: "{$e->getMessage()}")
        );

        Notification::make()
            ->title("账号 {$this->account?->account} 绑定失败 {$e->getMessage()}")
            ->body($e->getMessage())
            ->warning()
            ->sendToDatabase(User::get());
    }

    protected function handleBindException(Exception $exception, int $attempt): void
    {
        $this->logger->error("绑定失败 (尝试 {$attempt}): {$exception->getMessage()}", [
            'account' => $this->account->account,
            'phone' => $this->phone->phone,
        ]);

        if($exception instanceof PageException){
            $exception->getPage()->takeScreenshot("exception-{$attempt}.png");
        }

        $this->handlePhoneException($exception);

        Event::dispatch(new AccountBindPhoneFailEvent(account: $this->account, description: $exception->getMessage()));
    }
}
