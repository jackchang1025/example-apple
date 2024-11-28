<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage;

use Modules\AppleClient\Service\Integrations\AppleId\Dto\SecurityVerifyPhone\SecurityVerifyPhone;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class SecurityVerifyPhoneRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(protected string $countryCode, protected string $phoneNumber, protected string $countryDialCode, protected bool $nonFTEU = true)
    {
    }

    public function createDtoFromResponse(Response $response): SecurityVerifyPhone
    {
        return SecurityVerifyPhone::from($response->json());
    }

    public function resolveEndpoint(): string
    {
        return '/account/manage/security/verify/phone';
    }

    protected function defaultBody(): array
    {
        return [
            'phoneNumberVerification' => [
                'phoneNumber' => [
                    'countryCode' => $this->countryCode,
                    'number' => $this->phoneNumber,
                    'countryDialCode' => $this->countryDialCode,
                    'nonFTEU' => $this->nonFTEU,
                ],
                'mode' => 'sms',
            ],
        ];
    }
}
