<?php

namespace Modules\AppleClient\Service\Header;

use Saloon\Http\Response;
use Saloon\Contracts\ArrayStore;
use Saloon\Http\PendingRequest;

class HeaderSynchronize implements HeaderSynchronizeInterface
{

    public function __construct(protected ArrayStore $headerRepositories)
    {
    }

    public function withHeaderRepositories(ArrayStore $headerRepositories): self
    {
        $this->headerRepositories = $headerRepositories;

        return $this;
    }

    public function getHeaderRepositories(): ArrayStore
    {
        return $this->headerRepositories;
    }

    /**
     * 根据域名合并接口返回的 header到对应的 header 仓库中
     * @param Response $response
     * @return Response
     */
    public function extractHeader(Response $response): Response
    {
        $this->getHeaderRepositories()?->merge($response->headers()->all());

        return $response;
    }

    /**
     * @param PendingRequest $pendingRequest
     * @return PendingRequest
     */
    public function withHeader(PendingRequest $pendingRequest): PendingRequest
    {
        $persistentHeaders = [];
        $connector         = $pendingRequest->getConnector();
        $request           = $pendingRequest->getRequest();

        if (method_exists($connector, 'getPersistentHeaders')) {
            $persistentHeaders = array_merge($persistentHeaders, $connector->getPersistentHeaders()?->all());
        }

        if (method_exists($request, 'getPersistentHeaders')) {
            $persistentHeaders = array_merge($persistentHeaders, $request->getPersistentHeaders()?->all());
        }

        if (empty($persistentHeaders)) {
            return $pendingRequest;
        }

        $storedHeaders = $this->getHeaderRepositories()?->all();

        foreach ($persistentHeaders as $key => $value) {
            if (is_int($key)) {
                $header       = $value;
                $defaultValue = null;
            } else {
                $header       = $key;
                $defaultValue = $value;
            }

            $headerValue = $storedHeaders[$header] ?? null;

            if ($headerValue === null && $defaultValue !== null) {
                $headerValue = is_callable($defaultValue) ? $defaultValue() : $defaultValue;
            }

            if ($headerValue !== null) {
                $pendingRequest->headers()->add($header, $headerValue);
            }
        }

        return $pendingRequest;
    }
}
