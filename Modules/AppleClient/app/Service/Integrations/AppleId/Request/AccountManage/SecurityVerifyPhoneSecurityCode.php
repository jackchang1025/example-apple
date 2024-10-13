<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage;

use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

class SecurityVerifyPhoneSecurityCode extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $id,
        protected string $phoneNumber,
        protected string $countryCode,
        protected string $countryDialCode,
        protected string $code
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/account/manage/security/verify/phone/securitycode';
    }

    protected function defaultBody(): array
    {
        return [
            'phoneNumberVerification' => [
                'phoneNumber' => [
                    'id' => $this->id,
                    'number' => $this->phoneNumber,
                    'countryCode' => $this->countryCode,
                    'countryDialCode' => $this->countryDialCode,
                ],
                'securityCode' => [
                    'code' => $this->code,
                ],
                'mode' => 'sms',
            ],
        ];
    }
}
