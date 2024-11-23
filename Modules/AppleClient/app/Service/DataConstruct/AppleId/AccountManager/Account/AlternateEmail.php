<?php

namespace Modules\AppleClient\Service\DataConstruct\AppleId\AccountManager\Account;

use Modules\AppleClient\Service\DataConstruct\Data;

class AlternateEmail extends Data
{
    public function __construct(
        /** @var bool 是否显示重新发送链接 */
        public bool $showResendLink,

        /** @var bool 是否未验证 */
        public bool $notVetted,

        /** @var bool 是否待处理 */
        public bool $pending,

        /** @var bool 邮箱是否与账户名称相同 */
        public bool $isEmailSameAsAccountName,

        /** @var bool 是否已验证 */
        public bool $vetted
    ) {
    }

    /**
     * 获取验证状态
     */
    public function getVerificationStatus(): string
    {
        if ($this->vetted) {
            return 'verified';
        }
        if ($this->pending) {
            return 'pending';
        }
        if ($this->notVetted) {
            return 'not_verified';
        }

        return 'unknown';
    }

    /**
     * 检查是否可以重新发送验证
     */
    public function canResendVerification(): bool
    {
        return $this->showResendLink && ($this->notVetted || $this->pending);
    }

    /**
     * 检查是否可以添加为备用邮箱
     */
    public function canAddAsAlternate(): bool
    {
        return !$this->isEmailSameAsAccountName && !$this->vetted;
    }
}
