<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth;

use Modules\AppleClient\Service\Integrations\Idmsa\Dto\SendVerificationCode\SendDeviceSecurityCodeData;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class SendTrustedDeviceSecurityCode extends Request
{
    protected Method $method = Method::PUT;

    public function resolveEndpoint(): string
    {
        return '/appleauth/auth/verify/trusteddevice/securitycode';
    }

    public function createDtoFromResponse(Response $response): SendDeviceSecurityCodeData
    {
        return SendDeviceSecurityCodeData::from($response->json());
    }

    public function hasRequestFailed(Response $response): ?bool
    {
        if ($response->clientError() && $response->status() === 412) {
            return false;
        }

        return $response->serverError() || $response->clientError();
    }
}
