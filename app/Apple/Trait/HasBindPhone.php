<?php

namespace App\Apple\Trait;

use App\Apple\Exception\BindPhoneCodeException;
use App\Apple\Exception\MaxRetryAttemptsException;
use App\Models\Phone;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;
use Weijiajia\Exception\AppleClientException;
use Weijiajia\Exception\PhoneException;
use Weijiajia\Exception\PhoneNumberAlreadyExistsException;
use Weijiajia\Exception\VerificationCodeSentTooManyTimesException;
use Weijiajia\PhoneCode\Exception\AttemptBindPhoneCodeException;
use Weijiajia\PhoneCode\Helpers\PhoneCodeParser;

trait HasBindPhone
{
    /**
     * 绑定手机号至账户处理函数
     *
     * @param int $id 账户ID，用于获取并验证账户信息
     *
     * @throws \Throwable 当绑定过程中发生错误时抛出异常
     */
    public function handleBindPhone(int $id): void
    {
        try {

            $this->validateAccount($this->account = $this->getAccountById($id));

            $this->attemptBind();

        } catch (\Throwable|Exception  $e) {

            $this->handleException($e,0);
            throw $e;
        }
    }

    /**
     * 尝试进行手机号码绑定操作
     * @return void
     * @throws BindPhoneCodeException
     * @throws MaxRetryAttemptsException
     * @throws Throwable
     * @throws \JsonException
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     * @throws \Weijiajia\Exception\AccountLockoutException
     * @throws \Weijiajia\Exception\ErrorException
     * @throws \Weijiajia\Exception\StolenDeviceProtectionException
     */
    private function attemptBind(): void
    {
        for ($attempt = 1; $attempt <= $this->tries; $attempt++) {
            try {
                // 获取可用的手机号码并修改号码的状态
               $this->refreshPhone();

                // 绑定手机号码
                $this->bindPhoneToAccount();

                // 绑定成功后更新账号状态
                $this->handleBindSuccess();
                return;
            } catch (AppleClientException $e) {
                $this->handleException(exception: $e, attempt: $attempt);
            }
        }

        throw new MaxRetryAttemptsException(
            sprintf(
                "账号：%s 尝试 %d 次后绑定失败",
                $this->account->account,
                $attempt
            )
        );
    }


    /**
     * 绑定手机号到用户账户的方法
     * @return void
     * @throws AttemptBindPhoneCodeException
     * @throws BindPhoneCodeException
     * @throws PhoneException
     * @throws PhoneNumberAlreadyExistsException
     * @throws Throwable
     * @throws VerificationCodeSentTooManyTimesException
     * @throws \JsonException
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     * @throws \Weijiajia\Exception\AccountLockoutException
     * @throws \Weijiajia\Exception\ErrorException
     * @throws \Weijiajia\Exception\StolenDeviceProtectionException
     */
    private function bindPhoneToAccount(): void
    {
        //发送绑定手机号码的请求
        $response = $this->sendBindRequest(
                countryCode:$this->getPhone()->country_code,
                phoneNumber: $this->getPhone()->national_number,
                countryDialCode: $this->getPhone()->country_dial_code
            );

        // 获取号码 ID
        $id = $response->phoneNumberVerification()['phoneNumber']['id'] ?? null;
        if (empty($id)) {
            throw new BindPhoneCodeException(
                "绑定失败 phone: {$this->phone ->phone} 获取号码 ID 为空 body: {$response->body()}"
            );
        }

        // 这里循环获取手机验证码
        $code =  $this->getPhoneConnector()->attemptGetPhoneCode($this->getPhone()->phone_address,new PhoneCodeParser());

        // 验证手机验证码
        $this->appleClient->securityVerifyPhoneSecurityCode(
            id: $id,
            phoneNumber: $this->phone->national_number,
            countryCode: $this->phone->country_code,
            countryDialCode: $this->phone->country_dial_code,
            code: $code
        );
    }

    /**
     * 处理绑定手机号成功的业务逻辑。
     *
     * 在事务中更新账户与手机号的状态，确保数据一致性。
     * 成功后触发绑定成功的通知。
     *
     * @return void
     * @throws \Exception|Throwable
     */
    protected function handleBindSuccess(): void
    {
        DB::transaction(function () {
            $this->getAccount()->update([
                'bind_phone'         => $this->phone->phone,
                'bind_phone_address' => $this->phone->phone_address,
            ]);
            $this->getPhone()->update(['status' => Phone::STATUS_BOUND]);
        });

        $this->successNotification("绑定成功","账号： {$this->getAccount()->account} 绑定成功 手机号码：{$this->phone->phone}");
    }

    /**
     * 处理手机号相关异常情况
     *
     * 当遇到特定异常时，更新数据库中手机号的状态，并将该手机号添加到不合法列表中。
     *
     * @param Throwable $exception 需要处理的异常实例
     *
     * @return void
     */
    protected function handlePhoneException(Throwable $exception): void
    {
        if (!$this->phone){
            return;
        }

        $status = Phone::STATUS_NORMAL;
        if ($exception instanceof PhoneException){
            $status = Phone::STATUS_INVALID;
        }

        Phone::where('id',$this->phone->id)->update(['status' => $status]);

        $this->addNotInPhones($this->phone->id);
    }

    /**
     * 处理在绑定手机号过程中遇到的异常情况。
     *
     * @param \Throwable $exception 遇到的具体异常实例
     * @param int $attempt 当前是第几次尝试
     *
     * @return void
     *
     * @throws \Throwable 如果处理异常时发生其他错误，将重新抛出异常
     */
    protected function handleException(\Throwable $exception,int $attempt): void
    {
        $this->handlePhoneException($exception);

        $this->errorNotification("绑定失败","账号：{$this->getAccount()->account} 手机号码：{$this->phone?->phone} (尝试 {$attempt}) 绑定失败 错误消息：{$exception->getMessage()}");
    }

}
