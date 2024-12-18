<?php

namespace Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\AccountManager\Security;

use Modules\AppleClient\Service\DataConstruct\Data;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;

class Security extends Data
{
    public function __construct(

        /** @var Device[] 设备列表 */
        #[DataCollectionOf(Device::class)]
        public DataCollection $devices,

        /** @var bool 是否支持设备注销 */
        public bool $supportsDeviceSignout,

        /** @var int 最大允许的可信电话号码数量 */
        public int $maxAllowedTrustedPhones,

        /** @var array 密码策略 */
        public array $passwordPolicy,

        /** @var bool 是否符合 HSA2 资格 */
        public bool $hsa2Eligible,

        /** @var bool 是否存在安全问题 */
        public bool $questionsPresent,

        /** @var bool 是否允许 HSA 退出 */
        public bool $allowHSAOptOut,

        /** @var bool 是否有辅助密码 */
        public bool $hasSecondaryPassword,

        /** @var bool HSA 注册是否受限制 */
        public bool $isHSAEnrollmentEmbargoed,

        /** @var bool 联系邮箱是否已验证 */
        public bool $isContactEmailVerified,

        /** @var PhoneNumber[] 电话号码列表 */
        #[DataCollectionOf(PhoneNumber::class)]
        public DataCollection $phoneNumbers,

        /** @var string 出生日期 */
        public string $birthday
    ) {
    }
}
