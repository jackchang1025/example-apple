<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Integrations\AppleAuth\Request;

use Modules\AppleClient\Service\Integrations\AppleAuth\Dto\CompleteData;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class Complete extends Request implements HasBody
{
    use HasJsonBody;

    protected bool $proxyEnabled = false;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $key,
        protected string $b,
        protected string $salt,
        protected string $c,
        protected string $password,
        protected string $iteration = '20221',
        protected string $protocol = 's2k',
    ) {
    }

    public function resolveEndpoint(): string
    {
        return '/complete';
    }

    public function createDtoFromResponse(Response $response): CompleteData
    {
        return CompleteData::from($response->json());
    }

    public function defaultBody(): array
    {
        return [
            'key'   => $this->key,
            'value' => [
                'b'        => $this->b,
                'c'        => $this->c,
                'salt'     => $this->salt,
                'password' => $this->password,
                'iteration' => $this->iteration,
                'protocol' => $this->protocol,
            ],
        ];
    }
}
