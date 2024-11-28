<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth;

use Modules\AppleClient\Service\Integrations\Idmsa\Dto\VerifyPhoneSecurityCode\VerifyPhoneSecurityCodeData;
use Modules\AppleClient\Service\Integrations\Request;
use Modules\AppleClient\Service\Response\Response;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Traits\Body\HasJsonBody;

class VerifyPhoneSecurityCode extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $id,
        protected string $code
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/appleauth/auth/verify/phone/securitycode';
    }

    public function createDtoFromResponse(Response $response): VerifyPhoneSecurityCodeData
    {
        return VerifyPhoneSecurityCodeData::from($response->json());
    }

    public function defaultBody(): array
    {
        return[
            'phoneNumber' => [
                'id' => $this->id,
            ],
            'securityCode' => [
                'code' => $this->code,
            ],
            'mode' => 'sms',
        ];
    }
}
