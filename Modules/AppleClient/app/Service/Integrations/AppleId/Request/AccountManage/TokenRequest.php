<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Integrations\AppleId\Request\AccountManage;

use Modules\AppleClient\Service\Integrations\AppleId\Dto\Token\TokenData;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Enums\Method;
use Saloon\Http\Response;

class TokenRequest extends Request
{
    protected Method $method = Method::GET;


    public function createDtoFromResponse(Response $response): TokenData
    {
        return TokenData::from($response->json());
    }

    public function resolveEndpoint(): string
    {
        return '/account/manage/gs/ws/token';
    }
}
