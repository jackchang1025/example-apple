<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Request;

use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Dto\Request\SignInComplete;
use Modules\AppleClient\Service\Integrations\AppleAuthenticationConnector\Dto\Response\SignInComplete as SignInCompleteData;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class SignInCompleteRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected bool $proxyEnabled = false;

    protected Method $method = Method::POST;

    public function __construct(
        protected SignInComplete $data
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/complete';
    }

    public function createDtoFromResponse(Response $response): SignInCompleteData
    {
        return SignInCompleteData::from($response->json());
    }

    public function defaultBody(): array
    {
        return [
            'key'   => $this->data->key,
            'value' => [
                'b'         => $this->data->b,
                'c'         => $this->data->c,
                'salt'      => $this->data->salt,
                'password'  => $this->data->password,
                'iteration' => $this->data->iteration,
                'protocol'  => $this->data->protocol,
            ],
        ];
    }
}
