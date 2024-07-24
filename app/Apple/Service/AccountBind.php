<?php

declare(strict_types=1);

namespace App\Apple\Service;

use App\Apple\Service\Exception\AttemptBindPhoneCodeException;
use App\Apple\Service\Exception\MaxRetryAttemptsException;
use App\Apple\Service\Exception\BindPhoneCodeException;
use App\Apple\Service\Exception\UnauthorizedException;
use App\Models\Account;
use App\Models\Phone;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AccountBind
{
    // 定义类常量
    private const int MAX_RETRY_ATTEMPTS = 10;        // 最大重试次数
    private const int PHONE_CODE_RETRY_ATTEMPTS = 10;  // 获取手机验证码的最大尝试次数
    private const int PHONE_CODE_RETRY_DELAY = 10;    // 获取手机验证码的重试延迟（秒）

    private array $usedPhoneIds = [];  // 已使用的手机ID列表

    /**
     * 构造函数
     *
     * @param Apple $apple
     * @param LoggerInterface $logger 日志记录器
     * @param int $maxRetryAttempts
     * @param int $phoneCodeRetryAttempts
     * @param int $phoneCodeRetryDelay
     */
    public function __construct(
        private readonly Apple $apple,
        private readonly LoggerInterface $logger,
        private readonly int $maxRetryAttempts = self::MAX_RETRY_ATTEMPTS,
        private readonly int $phoneCodeRetryAttempts = self::PHONE_CODE_RETRY_ATTEMPTS,
        private readonly int $phoneCodeRetryDelay = self::PHONE_CODE_RETRY_DELAY
    ) {
    }

    /**
     * 处理账号绑定手机的主方法
     *
     * @param int $id 账号ID
     */
    public function handle(int $id): void
    {
        try {

            $account = $this->getAccount($id);
            if (!$account->password) {
                throw new \Exception("$account : 账号未设置密码");
            }

            if ($account->bind_phone) {
                throw new \Exception("{$account} :账号已经绑定手机号码");
            }

            $this->bindPhone($account);

        } catch (\Exception $e) {

            // 记录绑定失败的错误日志
            $account = $account ?? '';
            $this->logger->error("$account : {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * 绑定手机号码
     *
     * @param Account $account
     * @throws UnauthorizedException
     * @throws MaxRetryAttemptsException
     * @throws GuzzleException|\Exception|\Throwable
     */
    private function bindPhone(Account $account): void
    {
        $this->apple->appleId->accountManageToken();
        $this->apple->appleId->password($account->password);
        $this->attemptBind($account);
    }

    /**
     * 获取账号信息
     *
     * @param int $id 账号ID
     * @return Account
     * @throws ModelNotFoundException 当账号未找到时抛出
     */
    private function getAccount(int $id): Account
    {
        return Account::findOrFail($id);
    }

    /**
     * 尝试绑定手机号码
     *
     * @param Account $account 账号对象
     * @throws MaxRetryAttemptsException
     * @throws \Throwable
     */
    private function attemptBind(Account $account): void
    {
        for ($attempt = 1; $attempt <= $this->maxRetryAttempts; $attempt++) {
            try {

                $phone = $this->getAvailablePhone();
                $this->bindPhoneToAccount($account, $phone);
                return; // 成功绑定，退出循环
            } catch (BindPhoneCodeException $e) {
                $this->logger->warning('账号 {account} 第 {attempt} 次绑定手机 {phone} 失败: {message}', [
                    'attempt' => $attempt,
                    'account' => $account->account,
                    'phone'   => $phone->phone ?? 'unknown',
                    'message' => $e->getMessage(),
                ]);

                if (isset($phone)) {
                    $this->usedPhoneIds[] = $phone->id;
                }

                if ($attempt === $this->maxRetryAttempts) {
                    throw new MaxRetryAttemptsException(
                        sprintf("账号 %s 绑定手机号码失败: 超过最大重试次数 %d", $account->account, $this->maxRetryAttempts)
                    );
                }
            }
        }
    }

    /**
     * 处理绑定失败的情况
     *
     * @param Phone $phone
     * @param string $errorMessage
     */
    private function handleBindFailure(Phone $phone, string $errorMessage): void
    {
        try {
            $phone->update(['status' => Phone::STATUS_NORMAL]);
        } catch (\Exception $e) {
            // 如果更新失败，记录额外的错误日志
            $this->logger->error("更新手机 {$phone->phone} 状态失败: {$e->getMessage()}", [
                'phone'    => $phone->phone,
                'phone_id' => $phone->id,
            ]);
        }

        $this->logger->error("绑定手机号码 {$phone->phone} 失败: {$errorMessage}", [
            'phone'    => $phone->phone,
            'phone_id' => $phone->id,
        ]);
    }

    /**
     * 获取可用的手机号码
     *
     * @return Phone
     * @throws ModelNotFoundException|\Throwable 当没有可用的手机号码时抛出
     */
    private function getAvailablePhone(): Phone
    {
        //使用 DB::transaction 将整个操作包装在一个数据库事务中。
        //使用 lockForUpdate() 方法对查询加悲观锁，确保在事务完成之前，其他事务无法修改或选择相同的记录。
        //一旦获取到可用的手机号码，立即将其状态更新，进一步防止其他进程重复获取。
        //此外，我们还需要在绑定成功或失败后，相应地更新手机号码的状态：
        return DB::transaction(function () {
            $phone = Phone::where('status', Phone::STATUS_NORMAL)
                ->whereNotNull('phone_address')
                ->whereNotNull('phone')
                ->whereNotIn('id', $this->usedPhoneIds)
                ->lockForUpdate()
                ->firstOrFail();

            // 立即将手机状态更新为 'processing'，防止其他进程获取
            $phone->update(['status' => Phone::STATUS_BINDING]);

            return $phone;
        });
    }

    /**
     * 将手机号码绑定到账号
     *
     * @param Account $account 账号对象
     * @param Phone $phone 手机对象
     * @throws BindPhoneCodeException 当获取或验证手机验证码失败时抛出
     * @throws Exception\AttemptBindPhoneCodeException
     */
    private function bindPhoneToAccount(Account $account, Phone $phone): void
    {
        try {

            // 验证手机验证码
            $response = $this->apple->appleId->bindPhoneSecurityVerify(
                $phone->national_number,
                $phone->country_code,
                $phone->country_dial_code
            );

            $id = $response->phoneNumberVerification()['phoneNumber']['id'] ?? '';
            if (empty($id)) {
                throw new BindPhoneCodeException("获取绑定手机号码 {$phone->phone} id 为空");
            }

            //从接码平台获取验证码
            $code = $this->getPhoneCode($phone);

            $this->apple->appleId->manageVerifyPhoneSecurityCode(
                id: $id,
                phoneNumber: $phone->national_number,
                countryCode: $phone->country_code,
                countryDialCode: $phone->country_dial_code,
                code: $code
            );

            // 更新账号和手机信息
            $this->updateAccountAndPhone($account, $phone);

            $this->logger->info(
                '账号 {account} 绑定 {phone} 手机号码成功',
                ['account' => $account->account, 'phone' => $phone->phone]
            );

        } catch (BindPhoneCodeException $e) {

            $this->handleBindFailure($phone, $e->getMessage());
            throw $e;
        } catch (\Exception|\Throwable $e) {
            $this->handleBindFailure($phone, $e->getMessage());
            throw new BindPhoneCodeException("绑定手机号码 {$phone->phone} 时发生未知错误: ".$e->getMessage(), 0, $e);
        }
    }

    /**
     * 获取手机验证码
     *
     * @param Phone $phone 手机对象
     * @return string|null 返回验证码，如果获取失败则返回null
     * @throws GuzzleException|Exception\AttemptBindPhoneCodeException
     * @throws \Exception
     */
    public function getPhoneCode(Phone $phone): ?string
    {
        //TODO 检测验证码是否过期
        //防止在发送验证码之后马上通过接口去获取验证码，会拿到上一次的验证码我们睡眠 60 秒获取验证码
        sleep(60 * 2);
        $response = $this->apple->phoneCode->attemptGetPhoneCode(
            $phone->phone_address,
            $phone->phoneCodeParser(),
            $this->phoneCodeRetryAttempts,
            $this->phoneCodeRetryDelay
        );

        return $response->getData('code');
    }

    /**
     * 更新账号和手机信息
     *
     * @param Account $account 账号对象
     * @param Phone $phone 手机对象
     * @throws \Throwable
     */
    private function updateAccountAndPhone(Account $account, Phone $phone): void
    {
        //事务操作
        DB::transaction(function () use ($account, $phone) {
            $account->update([
                'bind_phone'         => $phone->phone,
                'bind_phone_address' => $phone->phone_address,
            ]);
            $phone->update(['status' => phone::STATUS_BOUND]);
        });
    }
}
