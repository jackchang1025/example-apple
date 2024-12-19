<?php

namespace Modules\AppleClient\Service\Integrations\ReportProblem\Request\Api\Purchase\Search;

use Modules\AppleClient\Service\Integrations\ReportProblem\Data\Response\Search\SearchResponse;
use Modules\AppleClient\Service\Integrations\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

class SearchRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected string $dsid,
        protected string $xAppleXsrfToken,
        protected ?string $batchId = null
    ) {
    }

    public function createDtoFromResponse(Response $response): SearchResponse
    {
        return SearchResponse::from($response->json());
    }

    public function defaultHeaders(): array
    {
        return [
            'X-Apple-Xsrf-Token' => $this->xAppleXsrfToken,
        ];
    }

    public function resolveEndpoint(): string
    {
        return '/api/purchase/search';
    }

    public function defaultBody(): array
    {
        return [
            'dsid' => $this->dsid,
            'batchId' => $this->batchId,
        ];
    }
}
