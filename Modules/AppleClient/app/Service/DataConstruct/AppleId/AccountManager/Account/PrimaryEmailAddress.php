<?php

namespace Modules\AppleClient\Service\DataConstruct\AppleId\AccountManager\Account;


use Modules\AppleClient\Service\DataConstruct\Data;

/**
 * 主电子邮件地址数据类
 */
class PrimaryEmailAddress extends Data
{
    public function __construct(
        /**
         * 电子邮件地址
         * @var string
         */
        public string $address,

        /**
         * 地址 ID
         * @var string
         */
        public string $id,

        /**
         * 地址类型
         * @var string
         */
        public string $type,

        /**
         * 验证状态
         * @var VettingStatus
         */
        public VettingStatus $vettingStatus,

        /**
         * 是否与账户名称相同
         * @var bool
         */
        public bool $isEmailSameAsAccountName,

        /**
         * 是否已验证
         * @var bool
         */
        public bool $vetted,
    ) {
    }
}
