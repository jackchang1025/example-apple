<?php

namespace Modules\AppleClient\Service\Integrations\Idmsa\Dto\Response\Auth;

use Modules\AppleClient\Service\DataConstruct\Data;

class TwoSV extends Data
{
    /**
     * @param array $supportedPushModes 支持的推送模式
     * @param PhoneNumberVerification $phoneNumberVerification 电话号码验证数据
     * @param array $authFactors 认证因素
     * @param string $source_returnurl 源返回URL
     * @param int $sourceAppId 源应用ID
     */
    public function __construct(
        public array $supportedPushModes,
        public PhoneNumberVerification $phoneNumberVerification,
        public array $authFactors,
        public string $source_returnurl,
        public int $sourceAppId
    ) {
    }
}
