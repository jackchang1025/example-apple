<?php

declare(strict_types=1);

namespace App\Apple\Service;

use App\Apple\Service\Exception\PhoneCodeException;
use App\Apple\Service\HttpClient;
use App\Models\Account;
use App\Models\Phone;
use Psr\Log\LoggerInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AccountBind
{
    // 定义类常量
    private const MAX_RETRY_ATTEMPTS = 10;        // 最大重试次数
    private const PHONE_CODE_RETRY_ATTEMPTS = 6;  // 获取手机验证码的最大尝试次数
    private const PHONE_CODE_RETRY_DELAY = 10;    // 获取手机验证码的重试延迟（秒）

    private array $usedPhoneIds = [];  // 已使用的手机ID列表

    /**
     * 构造函数
     *
     * @param HttpClient $httpClient HTTP客户端
     * @param LoggerInterface $logger 日志记录器
     */
    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly LoggerInterface $logger
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
                throw new \Exception('账号未设置密码');
            }

            if ($account->phone) {
                throw new \Exception('账号已经绑定手机号码');
            }

            $this->bindPhone($account);

        } catch (\Exception $e) {
            // 记录绑定失败的错误日志
            $this->logger->error('账号 {id} 绑定手机失败: {message}', [
                'id'        => $id,
                'message'   => $e->getMessage(),
                'exception' => $e
            ]);
        }
    }

    /**
     * 绑定手机号码
     *
     * @param Account $account
     */
    private function bindPhone(Account $account): void
    {
        $this->httpClient->accountManageToken();
        $this->httpClient->password($account->password);
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
     * @param int $attempt 当前尝试次数
     * @throws \Exception 当超过最大重试次数时抛出
     */
    private function attemptBind(Account $account, int $attempt = 1): void
    {
        if ($attempt > self::MAX_RETRY_ATTEMPTS) {
            throw new \Exception('绑定手机号码达到最大重试次数');
        }

        $phone = $this->getAvailablePhone();

        try {

            $this->bindPhoneToAccount($account, $phone);
        } catch (PhoneCodeException $e) {
            // 记录绑定失败的警告日志
            $this->logger->warning('账号 {account} 第 {attempt} 次绑定手机 {phone} 失败: {message}', [
                'attempt' => $attempt,
                'account' => $account->account,
                'phone'   => $phone->phone,
                'message' => $e->getMessage()
            ]);
            // 递归调用，尝试下一次绑定
            $this->attemptBind($account, $attempt + 1);
        }
    }

    /**
     * 获取可用的手机号码
     *
     * @return Phone
     * @throws ModelNotFoundException 当没有可用的手机号码时抛出
     */
    private function getAvailablePhone(): Phone
    {
        return Phone::where('status', 0)
            ->whereNotIn('id', $this->usedPhoneIds)
            ->firstOrFail();
    }

    /**
     * 将手机号码绑定到账号
     *
     * @param Account $account 账号对象
     * @param Phone $phone 手机对象
     * @throws PhoneCodeException 当获取或验证手机验证码失败时抛出
     */
    private function bindPhoneToAccount(Account $account, Phone $phone): void
    {
        $this->httpClient->accountManageSecurityVerifyPhone();

        $code = $this->getPhoneCode($phone);
        if (!$code) {
            $this->usedPhoneIds[] = $phone->id;
            throw new PhoneCodeException('获取手机验证码失败: ' . $phone->phone);
        }

        // 验证手机验证码
        $this->httpClient->verifyPhoneCode($code);

        // 更新账号和手机信息
        $this->updateAccountAndPhone($account, $phone);

        $this->logger->info('账号 {account} 绑定 {phone} 手机号码成功', ['account' => $account->account, 'phone' => $phone->phone]);
    }

    /**
     * 获取手机验证码
     *
     * @param Phone $phone 手机对象
     * @return string|null 返回验证码，如果获取失败则返回null
     */
    private function getPhoneCode(Phone $phone): ?string
    {
        for ($i = 0; $i < self::PHONE_CODE_RETRY_ATTEMPTS; $i++) {
            $response = $this->httpClient->getPhoneCode($phone->code);

            $code = $this->extractSixDigitNumber($response['data']['data'] ?? '');
            if ($code) {
                return $code;
            }
            sleep(self::PHONE_CODE_RETRY_DELAY);
        }
        return null;
    }

    /**
     * 更新账号和手机信息
     *
     * @param Account $account 账号对象
     * @param Phone $phone 手机对象
     */
    private function updateAccountAndPhone(Account $account, Phone $phone): void
    {
        $account->update([
            'phone'         => $phone->phone,
            'phone_address' => $phone->phone_address,
        ]);
        $phone->update(['status' => 1]);
    }

    /**
     * 从字符串中提取 6 位数字
     *
     * @param string $input 输入字符串
     * @return string|null 提取的 6 位数字，如果没有找到则返回 null
     */
    function extractSixDigitNumber(string $input): ?string
    {
        if (preg_match('/\b\d{6}\b/', $input, $matches)) {
            return $matches[0];
        }
        return null;
    }
}
