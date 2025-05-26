<?php

namespace App\Services;

use App\Apple\Enums\AccountStatus;
use App\Models\Phone;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Throwable;
use App\Models\Account;
use Filament\Notifications\Notification;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use App\Filament\Resources\AccountResource\Pages\ViewAccount;
use Weijiajia\SaloonphpAppleClient\Exception\BindPhoneException;
use Weijiajia\SaloonphpAppleClient\Exception\DescriptionNotAvailableException;
use Weijiajia\SaloonphpAppleClient\Exception\MaxRetryAttemptsException;
use Weijiajia\SaloonphpAppleClient\Exception\Phone\PhoneException;
use Weijiajia\SaloonphpAppleClient\Exception\Phone\PhoneNumberAlreadyExistsException;
use Weijiajia\SaloonphpAppleClient\Exception\StolenDeviceProtectionException;
use Weijiajia\SaloonphpAppleClient\Exception\VerificationCodeException;
use Weijiajia\SaloonphpAppleClient\Exception\VerificationCodeSentTooManyTimesException;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\Device\Device;
use App\Models\Devices;
use App\Models\Payment;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\SecurityVerifyPhone;
use App\Services\Integrations\Phone\Exception\AttemptGetPhoneCodeException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\AccountManager;
class AddSecurityVerifyPhoneService
{

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

    public const PHONE_BLACKLIST_KEY = 'phone_code_blacklist';
    public const BLACKLIST_EXPIRE_SECONDS = 3600; // 1小时过期

    public function __construct(
        private readonly Account $apple,
    ) {

    }

    public function handle(): void
    {
        
        $awat = $this->apple->cookieJar()?->getCookieByName('awat');
        if(!$awat || $awat->isExpired()){
            $this->apple->appleIdResource()->getAccountManagerResource()->token();
        }

        $this->fetchInfo();
        $this->handleAddSecurityVerifyPhone();
    }

    protected function fetchInfo(): void
    {
        try {

            if(!$this->apple->payment){
                $this->updateOrCreatePaymentConfig();
            }

            if($this->apple->devices->isEmpty()){
                $this->updateOrCreateDevices();
            }

            if(!$this->apple->accountManager){
                $this->updateOrCreateAccountManager();
            }

        } catch (Exception $e) {

            Notification::make()
            ->title("获取用户信息失败")
            ->body($e->getMessage())
            ->warning()
            ->actions([
                Action::make('view')
                    ->label('查看账户')
                    ->button()
                    ->url(ViewAccount::getUrl(['record' => $this->apple->id]), shouldOpenInNewTab: true),
            ])
            ->sendToDatabase(Auth::user() ?? User::all());

            Log::error($e);
        }
    }

    protected function updateOrCreateAccountManager(): void
    {
        $accountManager = $this->apple->appleIdResource()->getAccountManagerResource()->account();

        if($accountManager?->account){
            AccountManager::updateOrCreate(
                ['account_id' => $this->apple->id],
                $accountManager->toArray()
            );
        }
        
    }

    public function updateOrCreatePaymentConfig(): Payment
    {

        $primaryPaymentMethod = $this->apple->appleIdResource()
            ->getPaymentResource()
            ->getPayment()
            ->primaryPaymentMethod;

        return Payment::updateOrCreate(
            [
                'account_id' => $this->apple->id,
                'payment_id' => $primaryPaymentMethod->paymentId,
            ],
            $primaryPaymentMethod->toArray()
        );
    }

    /**
     * @return Collection
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function updateOrCreateDevices(): Collection
    {
        return $this->apple->appleIdResource()
            ->getDevicesResource()
            ->getDevicesDetails()
            ->toCollection()
            ->map(fn(Device $device) => Devices::updateOrCreate(
                [
                    'account_id' => $this->apple->id,
                    'device_id'  => $device->deviceId,
                ],
                $device->toArray()
            ));
    }

    public function handleAddSecurityVerifyPhone(): void
    {
        try {

            if ($this->apple->bind_phone) {
                throw new \RuntimeException("该账户已绑定手机号");
            }
            $this->apple->update(['status' => AccountStatus::BIND_ING]);

            $this->attemptBind();

        } catch (Throwable $e) {

            Log::error($e);

            if($e instanceof StolenDeviceProtectionException){
                $this->apple->update(['status' => AccountStatus::THEFT_PROTECTION]);
            }else{
                $this->apple->update(['status' => AccountStatus::BIND_FAIL]);
            }

            $this->apple->logs()
                ->create([
                    'action' => '添加授权号码失败', 
                    'request' => [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]
                ]);

            Notification::make()
                ->title("添加授权号码失败")
                ->body($this->formatFailMessage($e->getMessage()))
                ->warning()
                ->actions([
                    Action::make('view')
                        ->label('查看账户')
                        ->button()
                        ->url(ViewAccount::getUrl(['record' => $this->apple->id]), shouldOpenInNewTab: true),
                ])
                ->sendToDatabase(User::first());

            throw $e;
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
        for ($this->attempts = 1; $this->attempts <= 5; $this->attempts++) {
            try {

                $this->phone = $this->getAvailablePhone();

                $this->addSecurityVerifyPhone();

                $this->handleBindSuccess();
                return;

            } catch (VerificationCodeSentTooManyTimesException|PhoneException|PhoneNumberAlreadyExistsException|AttemptGetPhoneCodeException $e) {

                $this->handlePhoneException($e);

            }catch(StolenDeviceProtectionException $e){

                
                $this->handlePhoneException($e);
                throw $e;
            }catch(Throwable $e){

                $this->handlePhoneException($e);
                throw $e;
            }
        }

        throw new MaxRetryAttemptsException($this->formatFailMessage('达到最大重试次数，绑定失败。'));
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
                ->orderBy('id', 'desc')
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
     * @throws DescriptionNotAvailableException
     * @throws FatalRequestException
     * @throws AttemptGetPhoneCodeException
     * @throws PhoneException
     * @throws PhoneNumberAlreadyExistsException
     * @throws RequestException
     * @throws StolenDeviceProtectionException
     * @throws VerificationCodeException
     * @throws VerificationCodeSentTooManyTimesException
     */
    public function addSecurityVerifyPhone(): SecurityVerifyPhone
    {
        $response = $this->initiatePhoneVerification();

        $phoneRequest = $this->phone->makePhoneRequest();

        if($this->apple->debug()){
            $phoneRequest->debug();
        }

        $phoneRequest->middleware()->merge($this->apple->middleware());

        $code = $phoneRequest->attemptMobileVerificationCode();

        return $this->completePhoneVerification($response, $code);
    }

    /**
     * 发起手机验证
     * @return SecurityVerifyPhone
     * @throws PhoneNumberAlreadyExistsException
     * @throws VerificationCodeSentTooManyTimesException
     * @throws PhoneException
     * @throws StolenDeviceProtectionException
     * @throws FatalRequestException|RequestException
     * @throws DescriptionNotAvailableException
     */
    private function initiatePhoneVerification(): SecurityVerifyPhone
    {
        return $this->apple->appleIdResource()
            ->getSecurityPhoneResource()
            ->securityVerifyPhone(
                countryCode: $this->getPhone()->country_code,
                phoneNumber: $this->getPhone()->format(),
                countryDialCode: $this->getPhone()->country_dial_code
            );
    }

    public function getPhone(): ?Phone
    {
        return $this->phone;
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
        return $this->apple->appleIdResource()
            ->getSecurityPhoneResource()
            ->securityVerifyPhoneSecurityCode(
                id: $response->phoneNumberVerification->phoneNumber->id,
                phoneNumber: $this->getPhone()->format(),
                countryCode: $this->getPhone()->country_code,
                countryDialCode: $this->getPhone()->country_dial_code,
                code: $code
            );
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

    protected function handleBindSuccess(): void
    {
        $this->apple->status = AccountStatus::BIND_SUCCESS;
        $this->apple->bind_phone = $this->getPhone()->phone;
        $this->apple->bind_phone_address = $this->getPhone()->phone_address;
        $this->apple->save();

        $this->getPhone()->update(['status' => Phone::STATUS_BOUND]);

        Notification::make()
            ->title("添加授权号码成功")
            ->body($this->formatSuccessMessage())
            ->success()
            ->actions([
                Action::make('view')
                    ->label('查看账户')
                    ->button()
                    ->url(ViewAccount::getUrl(['record' => $this->apple->id]), shouldOpenInNewTab: true),
            ])
            ->sendToDatabase(User::first());

    }


    protected function handlePhoneException(Throwable $exception): void
    {
        if ($this->getPhone()) {
            $this->updatePhoneStatus($exception);
            $this->addNotInPhones($this->getPhone()->id);
        }
    }

    /**
     * 更新手机状态
     */
    private function updatePhoneStatus(Throwable $exception): void
    {
        $status = $this->determinePhoneStatus($exception);

        $this->updatePhoneInDatabase($this->getPhone()->id, ['status' => $status]);

        // 如果是验证码发送次数过多异常，将手机号加入黑名单
        if ($exception instanceof VerificationCodeSentTooManyTimesException) {

            // 使用 Redis Hash 添加记录
            $this->addActiveBlacklistIds($this->getPhone()->id);
        }
    }

    private function determinePhoneStatus(Throwable $e): string
    {
        if ($e instanceof PhoneException) {
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

    private function formatFailMessage(string $message = ''): string
    {
        return sprintf(
            "账号：%s 次数: %d 手机号码：%s  绑定失败：%s",
            $this->getApple()->appleid,
            $this->attempts,
            $this->getPhone()?->phone,
            $message
        );
    }

    public function getApple(): Account
    {
        return $this->apple;
    }
}
