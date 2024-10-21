<?php

namespace Modules\AppleClient\Service\DataConstruct\VerifyPhoneSecurityCode;

use Modules\AppleClient\Service\DataConstruct\HasFromResponse;
use Modules\AppleClient\Service\DataConstruct\PhoneNumber;
use Modules\AppleClient\Service\DataConstruct\SecurityCode;
use Modules\AppleClient\Service\Response\Response;
use Modules\AppleClient\Service\DataConstruct\Data;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;

class VerifyPhoneSecurityCode extends Data
{

    /**
     * @param DataCollection<PhoneNumber> $trustedPhoneNumbers 受信任的电话号码列表
     * @param PhoneNumber $phoneNumber 当前使用的电话号码
     * @param SecurityCode $securityCode 安全码相关信息
     * @param PhoneNumber $trustedPhoneNumber 安全码相关信息
     * @param string $mode 验证模式 (例如: "sms")
     * @param string $type 验证类型 (例如: "verification")
     * @param string $authenticationType 认证类型 (例如: "hsa2")
     * @param string $recoveryUrl 账户恢复 URL
     * @param string $cantUsePhoneNumberUrl 无法使用电话号码时的 URL
     * @param string $recoveryWebUrl Web 恢复 URL
     * @param string $repairPhoneNumberUrl 修复电话号码 URL
     * @param string $repairPhoneNumberWebUrl 修复电话号码 Web URL
     * @param string $aboutTwoFactorAuthenticationUrl 关于双因素认证的 URL
     * @param bool $autoVerified 是否自动验证
     */
    public function __construct(
        #[DataCollectionOf(PhoneNumber::class)]
        public DataCollection $trustedPhoneNumbers,
        public PhoneNumber $phoneNumber,
        public SecurityCode $securityCode,
        public PhoneNumber $trustedPhoneNumber,
        public string $mode,
        public string $type,
        public string $authenticationType,
        public string $recoveryUrl,
        public string $cantUsePhoneNumberUrl,
        public string $recoveryWebUrl,
        public string $repairPhoneNumberUrl,
        public string $repairPhoneNumberWebUrl,
        public string $aboutTwoFactorAuthenticationUrl,
        public bool $autoVerified,
    ) {
    }


}