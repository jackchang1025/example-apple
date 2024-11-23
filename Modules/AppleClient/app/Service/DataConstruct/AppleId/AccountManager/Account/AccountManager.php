<?php

namespace Modules\AppleClient\Service\DataConstruct\AppleId\AccountManager\Account;

use Modules\AppleClient\Service\DataConstruct\AppleId\AccountManager\Account\PageFeatures\PageFeatures;
use Modules\AppleClient\Service\DataConstruct\Data;

class AccountManager extends Data
{
    public function __construct(
        /** @var CountryFeatures 国家/地区功能特性配置 */
        public CountryFeatures $countryFeatures,

        /** @var string API密钥 */
        public string $apiKey,

        /** @var bool 是否符合HSA资格 */
        public bool $isHsaEligible,

        /** @var bool 是否启用从右到左显示 */
        public bool $enableRightToLeftDisplay,

        /** @var bool 登录句柄是否可用 */
        public bool $loginHandleAvailable,

        /** @var bool Apple ID是否与主邮箱相同 */
        public bool $isAppleIdAndPrimaryEmailSame,

        /** @var bool 是否显示受益人UI */
        public bool $shouldShowBeneficiaryUI,

        /** @var bool 是否显示NPA */
        public bool $showNpa,

        /** @var PageFeatures 页面特性配置 */
        public PageFeatures $pageFeatures,

        /** @var string 本地化的生日 */
        public string $localizedBirthday,

        /** @var bool 是否显示HSA2恢复密钥部分 */
        public bool $showHSA2RecoveryKeySection,

        /** @var string 姓名顺序 */
        public string $nameOrder,

        /** @var array 模块配置 */
        public array $modules,

        /** @var array 备用邮箱地址列表 */
        public array $alternateEmailAddresses,

        /** @var array 限制电话号码删除的国家列表 */
        public array $countriesWithPhoneNumberRemovalRestriction,

        /** @var AlternateEmail 添加备用邮箱配置 */
        public AlternateEmail $addAlternateEmail,

        /** @var bool 是否需要名字发音 */
        public bool $pronounceNamesRequired,

        /** @var array 本地化资源 */
        public array $localizedResources,

        /** @var string Apple ID显示 */
        public string $appleIDDisplay,

        /** @var Name 姓名信息 */
        public Name $name,

        /** @var bool 账户名称是否可编辑 */
        public bool $isAccountNameEditable,

        /** @var bool 是否显示数据恢复服务UI */
        public bool $shouldShowDataRecoveryServiceUI,

        /** @var bool 是否显示数据恢复服务UI */
        public bool $showDataRecoveryServiceUI,

//        /** @var PushEligibility 推送资格配置 */
//        public PushEligibility $pushEligibility,

        /** @var DisplayName 显示名称 */
        public DisplayName $displayName,

        /** @var array 支持链接 */
        public array $supportLinks,

        /** @var bool 是否需要中间名 */
        public bool $middleNameRequired,

        /** @var AppleID Apple ID信息 */
        public AppleID $appleID,

        /** @var PrimaryEmailAddress 主邮箱地址 */
        public PrimaryEmailAddress $primaryEmailAddress,

        /** @var bool 是否为HSA账户 */
        public bool $isHsa,

        /** @var string 人名顺序 */
        public string $personNameOrder,

        /** @var bool 是否允许添加备用邮箱 */
        public bool $shouldAllowAddAlternateEmail,

        /** @var bool 是否存在救援邮箱 */
        public bool $rescueEmailExists,

//        /** @var array 性别选项 */
//        #[DataCollectionOf(GenderOptionData::class)]
//        public array $genderOptions,

//        /** @var AddressFeaturesData 地址特性配置 */
//        public AddressFeaturesData $addressFeatures,

        /** @var bool 是否为付费账户 */
        public bool $isPAIDAccount,

        /** @var bool 是否混淆生日 */
        public bool $obfuscateBirthday,

        /** @var bool 是否超过验证尝试次数 */
        public bool $exceededVerificationAttempts,

        /** @var bool 是否启用非FTEU */
        public bool $nonFTEUEnabled,

        /** @var bool 名字是否不需要空格 */
        public bool $noSpaceRequiredInName,

        /** @var bool 是否启用重新设计的登录 */
        public bool $isRedesignSignInEnabled,

        /** @var PrimaryEmailAddress 主邮箱地址显示 */
        public PrimaryEmailAddress $primaryEmailAddressDisplay,

        /** @var array Apple ID邮箱合并 */
        public array $appleIDEmailMerge,

        /** @var bool 是否需要SCNT */
        public bool $scntRequired,

//        /** @var StaticResourcesData 静态资源 */
//        public StaticResourcesData $staticResources,

        /** @var bool 是否显示恢复密钥UI */
        public bool $shouldShowRecoveryKeyUI,

        /** @var string 环境 */
        public string $environment,

        /** @var bool 是否显示监护人UI */
        public bool $shouldShowCustodianUI,

        /** @var int 隐藏邮箱数量 */
        public int $hideMyEmailCount,

        /** @var int 消息中使用的人名最大长度 */
        public int $usePersonNameInMessagingMaxLength,

        /** @var Account 账户信息 */
        public Account $account,

        /** @var AlternateEmail 编辑备用邮箱 */
        public AlternateEmail $editAlternateEmail
    ) {
    }

    /**
     * 获取账户基本信息
     */
    public function getAccountBasicInfo(): array
    {
        return [
            'appleID'      => $this->appleIDDisplay,
            'name'         => $this->name,
            'birthday'     => $this->localizedBirthday,
            'primaryEmail' => $this->primaryEmailAddress,
        ];
    }

    /**
     * 检查是否为高级账户
     */
    public function isAdvancedAccount(): bool
    {
        return $this->isPAIDAccount || $this->isHsaEligible;
    }

    /**
     * 获取可用的安全特性
     */
    public function getAvailableSecurityFeatures(): array
    {
        return [
            'hsa2'         => $this->showHSA2RecoveryKeySection,
            'dataRecovery' => $this->shouldShowDataRecoveryServiceUI,
            'recoveryKey'  => $this->shouldShowRecoveryKeyUI,
        ];
    }

    /**
     * 检查账户是否需要额外验证
     */
    public function needsAdditionalVerification(): bool
    {
        return $this->exceededVerificationAttempts || $this->scntRequired;
    }

    /**
     * 获取账户UI配置
     */
    public function getUIConfiguration(): array
    {
        return [
            'rightToLeft'    => $this->enableRightToLeftDisplay,
            'beneficiaryUI'  => $this->shouldShowBeneficiaryUI,
            'custodianUI'    => $this->shouldShowCustodianUI,
            'dataRecoveryUI' => $this->showDataRecoveryServiceUI,
        ];
    }

    /**
     * 检查邮箱管理权限
     */
    public function canManageEmails(): bool
    {
        return $this->shouldAllowAddAlternateEmail && !$this->exceededVerificationAttempts;
    }
}
