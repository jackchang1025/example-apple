<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\AccountManager\PageFeatures;

use Modules\AppleClient\Service\DataConstruct\Data;

class PageFeatures extends Data
{
    public function __construct(
        /** @var bool 是否启用 Apple ID 品牌重塑 */
        public bool $shouldEnableAppleIDRebrand,

        /** @var bool 是否隐藏国家选择器 */
        public bool $hideCountrySelector,

        /** @var bool 是否显示隐私部分 */
        public bool $showPrivacySection,

        /** @var bool 是否显示可信电话号码 */
        public bool $showTrustedPhoneNumber,

        /** @var bool 是否启用 iForgot 重置 CR */
        public bool $isIForgotResetCREnabled,

        /** @var FeatureSwitches 功能开关配置 */
        public FeatureSwitches $featureSwitches,

        /** @var bool 是否显示隐私管理设置 */
        public bool $showPrivacyManageSettings,

        /** @var bool 是否显示生日 */
        public bool $showBirthday,

        /** @var bool 是否可编辑名字 */
        public bool $editName,

        /** @var string 默认语言 */
        public string $defaultLanguage,

        /** @var bool 是否显示额外的生日文本 */
        public bool $showExtraDOBText,

        /** @var bool 是否隐藏首选语言 */
        public bool $hidePreferredLanguage,

        /** @var bool 是否显示主地址 */
        public bool $showPrimaryAddress,

        /** @var bool 是否显示合规号码 */
        public bool $showComplianceNumber,

        /** @var bool 是否隐藏救援邮箱 */
        public bool $hideRescueEmail,

        /** @var bool 是否隐藏新闻通讯 */
        public bool $hideNewsletter,

        /** @var bool 是否显示主邮箱 */
        public bool $showPrimaryEmail,

        /** @var bool 是否可编辑联系邮箱 */
        public bool $editContactEmail,

        /** @var string 默认国家 */
        public string $defaultCountry
    ) {
    }

    /**
     * 获取隐私相关的设置
     */
    public function getPrivacySettings(): array
    {
        return [
            'showPrivacySection'        => $this->showPrivacySection,
            'showPrivacyManageSettings' => $this->showPrivacyManageSettings,
        ];
    }

    /**
     * 获取显示相关的设置
     */
    public function getDisplaySettings(): array
    {
        return [
            'showBirthday'         => $this->showBirthday,
            'showExtraDOBText'     => $this->showExtraDOBText,
            'showPrimaryAddress'   => $this->showPrimaryAddress,
            'showComplianceNumber' => $this->showComplianceNumber,
            'showPrimaryEmail'     => $this->showPrimaryEmail,
        ];
    }

    /**
     * 获取编辑权限设置
     */
    public function getEditPermissions(): array
    {
        return [
            'editName'         => $this->editName,
            'editContactEmail' => $this->editContactEmail,
        ];
    }
}
