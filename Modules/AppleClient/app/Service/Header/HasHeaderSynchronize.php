<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service\Header;

use Saloon\Enums\PipeOrder;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;
use Saloon\Repositories\ArrayStore;

trait HasHeaderSynchronize
{
    protected ?HeaderSynchronizeInterface $headerRepositories = null;

    public function withHeaderRepositories(HeaderSynchronizeInterface|array|null $headerRepositories): static
    {
        if (is_array($headerRepositories)) {
            $headerRepositories = new HeaderSynchronize(new ArrayStore($headerRepositories));
        }
        $this->headerRepositories = $headerRepositories;

        return $this;
    }

    public function getHeaderRepositories(): ?HeaderSynchronizeInterface
    {
        return $this->headerRepositories;
    }

    public function bootHasHeaderSynchronize(PendingRequest $pendingRequest): void
    {
        if (!$this->getHeaderRepositories()) {
            return;
        }

        $pendingRequest->middleware()
            ->onRequest(
                fn(PendingRequest $pendingRequest) => $this->getHeaderRepositories()?->withHeader($pendingRequest),
                'header_store_request',
                PipeOrder::LAST
            );

        $pendingRequest->middleware()
            ->onResponse(fn(Response $response) => $this->getHeaderRepositories()?->extractHeader($response),
                'header_store_response',
                PipeOrder::LAST);
    }
}
