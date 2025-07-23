<?php

namespace App\Services;

use App\Models\Phone;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Throwable;
use Weijiajia\SaloonphpAppleClient\Exception\Phone\PhoneException;
use Weijiajia\SaloonphpAppleClient\Exception\VerificationCodeSentTooManyTimesException;

/**
 * 手机号管理器
 * 负责手机号的获取、状态管理和黑名单处理
 */
class PhoneManager
{
    /**
     * @var array 当前会话中已尝试的无效手机号ID列表
     */
    private array $excludedPhoneIds = [];

    public function __construct(
        private readonly BlacklistManager $blacklistManager
    ) {}

    /**
     * 获取一个可用的手机号
     *
     * @return Phone
     * @throws ModelNotFoundException 当没有可用手机号时抛出
     */
    public function getAvailablePhone(): Phone
    {
        return DB::transaction(function () {
            $phone = $this->findAvailablePhone();
            $this->markPhoneAsBinding($phone);
            return $phone;
        });
    }

    /**
     * 将手机号标记为已绑定
     *
     * @param Phone $phone
     * @return void
     */
    public function markPhoneAsBound(Phone $phone): void
    {
        $phone->update(['status' => Phone::STATUS_BOUND]);
    }

    /**
     * 处理手机号异常
     *
     * @param Phone $phone
     * @param Throwable $exception
     * @return void
     */
    public function handlePhoneException(Phone $phone, Throwable $exception): void
    {
        $this->updatePhoneStatus($phone, $exception);
        $this->addToExcludedList($phone->id);

        // 如果是验证码发送次数过多，加入黑名单
        if ($exception instanceof VerificationCodeSentTooManyTimesException) {
            $this->blacklistManager->addToBlacklist($phone->id);
        }
    }

    /**
     * 添加手机号到当前会话的排除列表
     *
     * @param int $phoneId
     * @return void
     */
    public function addToExcludedList(int $phoneId): void
    {
        $this->excludedPhoneIds[] = $phoneId;
    }

    /**
     * 获取排除的手机号ID列表
     *
     * @return array
     */
    public function getExcludedPhoneIds(): array
    {
        return $this->excludedPhoneIds;
    }

    /**
     * 查找可用的手机号
     *
     * @return Phone
     * @throws ModelNotFoundException
     */
    private function findAvailablePhone(): Phone
    {
        $blacklistIds = $this->blacklistManager->getActiveBlacklistIds();

        return Phone::query()
            ->where('status', Phone::STATUS_NORMAL)
            ->whereNotNull(['phone_address', 'phone'])
            ->whereNotIn('id', $this->excludedPhoneIds)
            ->whereNotIn('id', $blacklistIds)
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * 将手机号标记为正在绑定
     *
     * @param Phone $phone
     * @return void
     */
    private function markPhoneAsBinding(Phone $phone): void
    {
        $phone->update(['status' => Phone::STATUS_BINDING]);
    }

    /**
     * 根据异常更新手机号状态
     *
     * @param Phone $phone
     * @param Throwable $exception
     * @return void
     */
    private function updatePhoneStatus(Phone $phone, Throwable $exception): void
    {
        $status = $this->determinePhoneStatus($exception);
        $phone->update(['status' => $status]);
    }

    /**
     * 根据异常类型确定手机号状态
     *
     * @param Throwable $exception
     * @return string
     */
    private function determinePhoneStatus(Throwable $exception): string
    {
        return match (true) {
            $exception instanceof PhoneException => Phone::STATUS_INVALID,
            default => Phone::STATUS_NORMAL,
        };
    }
}
