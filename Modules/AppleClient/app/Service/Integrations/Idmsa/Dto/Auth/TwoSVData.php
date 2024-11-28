<?php

namespace Modules\AppleClient\Service\Integrations\Idmsa\Dto\Auth;

use Modules\AppleClient\Service\DataConstruct\Data;

class TwoSVData extends Data
{
    /**
     * @param array $supportedPushModes 支持的推送模式
     * @param PhoneNumberVerificationData $phoneNumberVerification 电话号码验证数据
     * @param array $authFactors 认证因素
     * @param string $source_returnurl 源返回URL
     * @param int $sourceAppId 源应用ID
     */
    public function __construct(
        public array $supportedPushModes,
        public PhoneNumberVerificationData $phoneNumberVerification,
        public array $authFactors,
        public string $source_returnurl,
        public int $sourceAppId
    ) {
    }
}
