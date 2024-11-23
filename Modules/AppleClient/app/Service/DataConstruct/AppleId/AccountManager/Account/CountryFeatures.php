<?php

namespace Modules\AppleClient\Service\DataConstruct\AppleId\AccountManager\Account;

use Modules\AppleClient\Service\DataConstruct\Data;

class CountryFeatures extends Data
{

    public function __construct(
        /**
         * 是否显示性别选项
         */
        public bool $showGender = true,
        /**
         * 是否禁用支付管理
         * @var bool
         */
        public bool $disablePaymentManagement = false
    ) {
    }
}
