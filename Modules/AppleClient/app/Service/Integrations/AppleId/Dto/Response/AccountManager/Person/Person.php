<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\AccountManager\Person;


use Modules\AppleClient\Service\DataConstruct\Data;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\AccountManager\Account\AppleID;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\AccountManager\Account\Person\ReachableAtOptions\ReachableAtOptions;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\AccountManager\Account\PrimaryEmailAddress;

/**
 * 个人信息数据类
 */
class Person extends Data
{
    public function __construct(
        /**
         * 主地址
         * @var PrimaryAddress
         */
        public PrimaryAddress $primaryAddress,

        /**
         * 可达选项
         * @var ReachableAtOptions
         */
        public ReachableAtOptions $reachableAtOptions,

        /**
         * Apple ID 信息
         * @var AppleID
         */
        public AppleID $appleID,

        /**
         * 主电子邮件地址
         * @var PrimaryEmailAddress
         */
        public PrimaryEmailAddress $primaryEmailAddress,

        /**
         * 配送地址列表
         * @var array
         */
        public array $shippingAddresses,

        /**
         * 登录句柄
         * @var LoginHandles
         */
        public LoginHandles $loginHandles,

        /**
         * 是否允许添加额外的备用邮箱
         * @var bool
         */
        public bool $allowAdditionalAlternateEmail,

        /**
         * 允许的最大共享号码数量
         * @var int
         */
        public int $maxAllowedSharedNumbers,

        /**
         * 是否未成年
         * @var bool
         */
        public bool $isUnderAge,

        /**
         * 是否有家庭成员
         * @var bool
         */
        public bool $hasFamily,

        /**
         * 是否是家庭组织者
         * @var bool
         */
        public bool $isFamilyOrganizer,

        /**
         * 生日是否可编辑
         * @var bool
         */
        public bool $isDateOfBirthEditable,

        /**
         * 是否符合 HSA2 条件
         * @var bool
         */
        public bool $isHSA2Eligible,

        /**
         * 是否需要 GDPR 儿童同意
         * @var bool
         */
        public bool $requiresGdprChildConsent,

        /**
         * 最小生日日期
         * @var string
         */
        public string $minBirthday,

        /**
         * 最大生日日期
         * @var string
         */
        public string $maxBirthday,

        /**
         * 账户名称
         * @var string
         */
        public string $accountName,

        /**
         * 电话号码列表
         * @var array
         */
        public array $phoneNumbers,

        /**
         * 是否为管理管理员
         * @var bool
         */
        public bool $managedAdministrator,

        /**
         * 生日
         * @var string
         */
        public string $birthday,

        /**
         * 默认配送地址
         * @var DefaultShippingAddress
         */
        public DefaultShippingAddress $defaultShippingAddress,

        /**
         * 允许的最大备用邮箱数量
         * @var int
         */
        public int $maxAllowedAlternateEmails,

        /**
         * 是否有支付方式
         * @var bool
         */
        public bool $hasPaymentMethod,

        /**
         * 格式化的账户名称
         * @var string
         */
        public string $formattedAccountName,

        /**
         * 姓名
         * @var Name
         */
        public Name $name,
    ) {
    }
}
