<?php

namespace Modules\AppleClient\Service;

use App\Apple\Enums\AccountStatus;
use App\Models\Phone;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\AppleClient\Events\AccountBindPhoneFailEvent;
use Modules\AppleClient\Events\AccountBindPhoneSuccessEvent;
use Modules\AppleClient\Service\Exception\AccountException;
use Modules\AppleClient\Service\Exception\AppleClientException;
use Modules\AppleClient\Service\Exception\BindPhoneCodeException;
use Modules\AppleClient\Service\Exception\BindPhoneException;
use Modules\AppleClient\Service\Exception\ErrorException;
use Modules\AppleClient\Service\Exception\MaxRetryAttemptsException;
use Modules\AppleClient\Service\Exception\PhoneException;
use Modules\AppleClient\Service\Exception\PhoneNotFoundException;
use Modules\AppleClient\Service\Exception\PhoneNumberAlreadyExistsException;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Exception\VerificationCodeException;
use Modules\AppleClient\Service\Exception\VerificationCodeSentTooManyTimesException;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\Device\Device;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\SecurityVerifyPhone;
use Modules\AppleClient\Service\Trait\HasNotification;
use Modules\AppleClient\Service\Trait\HasTries;
use Modules\PhoneCode\Service\Exception\AttemptBindPhoneCodeException;
use Modules\PhoneCode\Service\Helpers\PhoneCodeParser;
use Modules\PhoneCode\Service\PhoneCodeService;
use Psr\Log\LoggerInterface;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Throwable;

/**
 * Apple账户安全验证手机号添加服务类
 *
 * 该服务负责处理Apple账户绑定安全验证手机号的完整流程，包括:
 * - 手机号验证码的发送和验证
 * - 重试机制的实现
 * - 异常处理
 * - 事件分发
 */
class AddSecurityVerifyPhoneService
{
    use HasTries;
    use HasNotification;

    /**
     * @var Phone|null 当前正在处理的手机号对象
     */
    private ?Phone $phone = null;

    /**
     * @var array 已尝试过的无效手机号ID列表
     */
    private array $notInPhones = [];

    /**
     * @var int 当前尝试次数
     */
    private int $attempts = 1;

    // 添加类常量
    public const string PHONE_BLACKLIST_KEY = 'phone_code_blacklist';
    public const int BLACKLIST_EXPIRE_SECONDS = 3600; // 1小时过期

    /**
     * 构造函数
     *
     * @param Apple $apple Apple服务实例
     * @param PhoneCodeService $phoneCodeService 手机验证码服务
     * @param Dispatcher $dispatcher 事件分发器
     * @param LoggerInterface $logger 日志记录器
     * @param PhoneCodeParser $phoneCodeParser 手机验证码解析器
     */
    public function __construct(
        private readonly Apple $apple,
        private readonly PhoneCodeService $phoneCodeService,
        private readonly Dispatcher $dispatcher,
        private readonly LoggerInterface $logger,
        private readonly PhoneCodeParser $phoneCodeParser = new PhoneCodeParser()
    ) {
        $this->initializeRetrySettings();
    }

    /**
     * 初始化重试设置
     * 设置最大重试次数、重试间隔和指数退避策略
     */
    protected function initializeRetrySettings(): void
    {
        $this->withTries(5)
            ->withRetryInterval(1000)
            ->withUseExponentialBackoff();
    }

    /**
     * 处理手机验证的主要流程
     *
     * @throws AccountException 账户相关异常
     * @throws MaxRetryAttemptsException 超过最大重试次数异常
     * @throws PhoneNotFoundException 手机号未找到异常
     * @throws Throwable 其他异常
     */
    public function handle(): void
    {
        $this->fetchInfo();
        $this->handleAddSecurityVerifyPhone();
    }

    /**
     *
     *  获取用户信息
     *  包括支付配置和设备信息的更新或创建
     * @return void
     */
    protected function fetchInfo(): void
    {
        try {
            $this->updateOrCreatePaymentConfig();
            $this->updateOrCreateDevices();
        } catch (Exception $e) {
            $this->errorNotification("获取用户信息失败", $e->getMessage());
        }
    }

    /**
     * 更新或创建支付配置信息
     * @return \App\Models\Payment
     * @throws FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function updateOrCreatePaymentConfig(): \App\Models\Payment
    {
        return $this->apple->getWebResource()
            ->getAppleIdResource()
            ->getPaymentResource()
            ->getPayment()
            ->primaryPaymentMethod
            ->updateOrCreate($this->apple->getAccount()->model()->id);
    }

    /**
     * 更新或创建设备信息
     * @return Collection
     * @throws FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    public function updateOrCreateDevices(): Collection
    {
        return $this->apple->getWebResource()
            ->getAppleIdResource()
            ->getDevicesResource()
            ->getDevicesDetails()
            ->toCollection()
            ->map(fn(Device $device) => $device->deviceDetail->updateOrCreate($this->apple->getAccount()->model()->id));
    }

    /**
     * 处理添加安全验证手机号的核心逻辑
     *
     * @throws AccountException 账户相关异常
     * @throws MaxRetryAttemptsException 超过最大重试次数异常
     * @throws PhoneNotFoundException 手机号未找到异常
     * @throws Throwable 其他异常
     */
    public function handleAddSecurityVerifyPhone(): void
    {
        try {

            $this->validateAccount();

            $this->attemptBind();

        } catch (Throwable $e) {

            $this->handleException($e);

            $this->dispatchFailEvent($e);
            throw $e;
        }
    }

    /**
     * 验证账户状态
     * 检查账户是否已经绑定了手机号
     *
     * @throws AccountException 如果账户已绑定手机号则抛出异常
     */
    public function validateAccount(): void
    {
        if ($this->apple->getAccount()->getBindPhone()) {
            throw new AccountException("该账户已绑定手机号");
        }
    }

    /**
     * 尝试绑定手机号 实现重试机制，在失败时自动重试
     * @return void
     * @throws BindPhoneException
     * @throws FatalRequestException
     * @throws MaxRetryAttemptsException
     * @throws ModelNotFoundException
     * @throws RequestException|Throwable
     */
    public function attemptBind(): void
    {
        $tries = $this->getTries() ?: 1;

        for ($this->attempts = 1; $this->attempts <= $tries; $this->attempts++) {
            try {

                //为了防止手机号码重复绑定，每次绑定前刷新一次手机号码
                $this->phone = null;

                $this->refreshAvailablePhone();

                $this->addSecurityVerifyPhone();

                $this->handleBindSuccess();

                return;

            } catch (AppleClientException|AttemptBindPhoneCodeException|BindPhoneCodeException $e) {

                $this->handleException($e);

                if ($e instanceof StolenDeviceProtectionException) {

                    $model = $this->apple->getAccount()->model();
                    $model->update(['status' => AccountStatus::THEFT_PROTECTION]);
                    throw $e;
                }

                $this->dispatchFailEvent($e);
            }
        }

        throw new MaxRetryAttemptsException($this->formatMaxRetriesMessage());
    }

    /**
     * 刷新可用手机号，如果传入 $phone 则直接设置
     *
     * @param Phone|null $phone 可选的手机号对象，用于测试或覆盖默认行为
     * @return Phone
     * @throws Throwable
     */
    public function refreshAvailablePhone(?Phone $phone = null): Phone
    {
        if ($phone !== null) {
            // 直接设置传入的手机号对象，用于测试的注入
            $this->phone = $phone;

            return $this->phone;
        }

        return $this->phone = $this->getAvailablePhone();
    }

    /**
     * 获取当前有效的黑名单手机号ID
     *
     * @return array
     */
    protected function getActiveBlacklistIds(): array
    {

        // 获取所有黑名单记录
        $blacklist = Redis::hgetall(self::PHONE_BLACKLIST_KEY);

        // 过滤出未过期的黑名单手机号ID
        return array_keys(array_filter($blacklist, function ($timestamp) {
            return (now()->timestamp - $timestamp) < self::BLACKLIST_EXPIRE_SECONDS;
        }));
    }

    protected function addActiveBlacklistIds(int $id): void
    {
        Redis::hset(self::PHONE_BLACKLIST_KEY, $id, now()->timestamp);
        Redis::expire(self::PHONE_BLACKLIST_KEY, self::BLACKLIST_EXPIRE_SECONDS);
    }

    /**
     * 获取可用手机号
     * 从数据库中查询并锁定一个可用的手机号
     *
     * @return Phone 可用的手机号实例
     * @throws Throwable
     * @throws ModelNotFoundException 当没有可用手机号时抛出
     */
    protected function getAvailablePhone(): Phone
    {
        return DB::transaction(function () {
            // 获取有效黑名单ID
            $blacklistIds = $this->getActiveBlacklistIds();

            $phone = Phone::query()
                ->where('status', Phone::STATUS_NORMAL)
                ->whereNotNull(['phone_address', 'phone'])
                ->whereNotIn('id', $this->getNotInPhones())
                ->whereNotIn('id', $blacklistIds)
                ->lockForUpdate()
                ->firstOrFail();

            $phone->update(['status' => Phone::STATUS_BINDING]);

            return $phone;
        });
    }

    public function getNotInPhones(): array
    {
        return $this->notInPhones;
    }

    public function setNotInPhones(array $notInPhones): void
    {
        $this->notInPhones = $notInPhones;
    }

    /**
     * 添加安全验证手机号的完整流程
     * 包括发送验证码、等待间隔、验证码验证等步骤
     *
     * @return SecurityVerifyPhone 验证结果
     * @throws AttemptBindPhoneCodeException 验证码绑定异常
     * @throws BindPhoneException
     * @throws ErrorException
     * @throws FatalRequestException
     * @throws PhoneException
     * @throws PhoneNumberAlreadyExistsException
     * @throws StolenDeviceProtectionException
     * @throws VerificationCodeException
     * @throws VerificationCodeSentTooManyTimesException
     * @throws RequestException
     */
    public function addSecurityVerifyPhone(): SecurityVerifyPhone
    {
        $response = $this->initiatePhoneVerification();

        $this->waitForRetryInterval();

        $code = $this->getVerificationCode();

        $response = $this->completePhoneVerification($response, $code);

        $this->dispatchSuccessEvent();

        return $response;
    }

    /**
     * 发起手机验证
     * @return SecurityVerifyPhone
     * @throws BindPhoneException
     * @throws ErrorException
     * @throws PhoneNumberAlreadyExistsException
     * @throws VerificationCodeSentTooManyTimesException
     * @throws PhoneException
     * @throws StolenDeviceProtectionException
     * @throws FatalRequestException|RequestException
     */
    private function initiatePhoneVerification(): SecurityVerifyPhone
    {
        return $this->apple->getWebResource()
            ->getAppleIdResource()
            ->getSecurityPhoneResource()
            ->securityVerifyPhone(
                countryCode: $this->getPhone()->country_code,
                phoneNumber: $this->getPhone()->national_number,
                countryDialCode: $this->getPhone()->country_dial_code
            );
    }

    public function getPhone(): ?Phone
    {
        return $this->phone;
    }

    /**
     * 等待重试间隔
     */
    private function waitForRetryInterval(): void
    {
        usleep(
            $this->getSleepTime(
                $this->attempts,
                $this->getRetryInterval(),
                $this->getUseExponentialBackoff()
            )
        );
    }

    /**
     * 获取验证码
     * @return string
     * @throws AttemptBindPhoneCodeException
     * @throws FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    private function getVerificationCode(): string
    {
        return $this->getPhoneCodeService()->attemptGetPhoneCode(
            $this->getPhone()->phone_address,
            $this->phoneCodeParser
        );
    }

    public function getPhoneCodeService(): PhoneCodeService
    {
        return $this->phoneCodeService;
    }

    /**
     * 完成手机验证
     * @param SecurityVerifyPhone $response
     * @param string $code
     * @return SecurityVerifyPhone
     * @throws Exception|VerificationCodeException
     * @throws FatalRequestException
     */
    private function completePhoneVerification(SecurityVerifyPhone $response, string $code): SecurityVerifyPhone
    {
        return $this->apple->getWebResource()
            ->getAppleIdResource()
            ->getSecurityPhoneResource()
            ->securityVerifyPhoneSecurityCode(
                id: $response->phoneNumberVerification->phoneNumber->id,
                phoneNumber: $this->getPhone()->national_number,
                countryCode: $this->getPhone()->country_code,
                countryDialCode: $this->getPhone()->country_dial_code,
                code: $code
            );
    }

    /**
     * 分发成功事件
     */
    private function dispatchSuccessEvent(): void
    {
        $this->getDispatcher()?->dispatch(
            new AccountBindPhoneSuccessEvent(
                account: $this->apple->getAccount(),
                addSecurityVerifyPhone: $this->getPhone(),
                message: $this->formatSuccessMessage()
            )
        );
    }

    public function getDispatcher(): ?Dispatcher
    {
        return $this->dispatcher;
    }

    /**
     * 格式化成功消息
     */
    private function formatSuccessMessage(): string
    {
        return sprintf(
            "次数: %d 手机号码: %s 绑定成功",
            $this->attempts,
            $this->getPhone()?->phone
        );
    }


    /**
     * 处理绑定成功
     */
    protected function handleBindSuccess(): void
    {
        $this->successNotification(
            "绑定成功",
            $this->formatSuccessMessage()
        );
    }

    /**
     * 处理异常
     */
    protected function handleException(Throwable $exception): void
    {
        if ($this->getPhone()) {
            $this->updatePhoneStatus($exception);
            $this->addNotInPhones($this->getPhone()->id);
        }

        $this->errorNotification(
            "绑定失败",
            $this->formatFailMessage($exception)
        );
    }

    /**
     * 更新手机状态
     */
    private function updatePhoneStatus(Throwable $exception): void
    {
        $status = $this->determinePhoneStatus($exception);

        // 使用新方法更新数据库
        $this->updatePhoneInDatabase($this->getPhone()->id, ['status' => $status]);

        // 如果是验证码发送次数过多异常，将手机号加入黑名单
        if ($exception instanceof VerificationCodeSentTooManyTimesException) {

            // 使用 Redis Hash 添加记录
            $this->addActiveBlacklistIds($this->getPhone()->id);
        }
    }

    private function determinePhoneStatus(Throwable $e): string
    {
        if ($e instanceof PhoneException || $e instanceof PhoneNumberAlreadyExistsException) {
            return Phone::STATUS_INVALID;
        }

        return Phone::STATUS_NORMAL;
    }

    public function updatePhoneInDatabase(int $phoneId, array $attributes): bool
    {
        return Phone::where('id', $phoneId)->update($attributes);
    }

    public function addNotInPhones(int|string $id): void
    {
        $this->notInPhones[] = $id;
    }

    /**
     * 格式化失败消息
     */
    private function formatFailMessage(Throwable $e): string
    {
        return sprintf(
            "次数：%d 手机号码：%s 绑定失败 消息: %s",
            $this->attempts,
            $this->getPhone()?->phone,
            $e->getMessage()
        );
    }

    /**
     * 分发失败事件
     */
    private function dispatchFailEvent(Throwable $e): void
    {
        $this->getDispatcher()?->dispatch(
            new AccountBindPhoneFailEvent(
                account: $this->apple->getAccount(),
                addSecurityVerifyPhone: $this->getPhone(),
                message: $this->formatFailMessage($e)
            )
        );
    }

    /**
     * 格式化最大重试消息
     */
    private function formatMaxRetriesMessage(): string
    {
        return sprintf(
            "账号：%s 尝试 %d 次后绑定失败",
            $this->getApple()->getAccount()->account,
            $this->attempts
        );
    }

    public function getApple(): Apple
    {
        return $this->apple;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
