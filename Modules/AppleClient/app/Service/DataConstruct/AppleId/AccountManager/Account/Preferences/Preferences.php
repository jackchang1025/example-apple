<?php

namespace Modules\AppleClient\App\Service\DataConstruct\AppleId\AccountManager\Account\Preferences;

use Modules\AppleClient\Service\DataConstruct\Data;

class Preferences extends Data
{
    public function __construct(
        /** @var string 首选语言 */
        public string $preferredLanguage,

        /** @var MarketingPreferences 营销偏好设置 */
        public MarketingPreferences $marketingPreferences,

        /** @var PrivacyPreferences 隐私偏好设置 */
        public PrivacyPreferences $privacyPreferences
    ) {
    }
}
