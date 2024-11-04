<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Integrations\Idmsa\Request\AppleAuth;

use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Enums\Method;

class AuthRepairComplete extends Request
{
    protected Method $method = Method::POST;

    public function resolveEndpoint(): string
    {
        return '/appleauth/auth/repair/complete';
    }
}
