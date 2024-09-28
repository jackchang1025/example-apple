<?php

namespace App\Selenium;

use App\Selenium\Request\Request;
use App\Selenium\Trait\HasPipeline;
use App\Selenium\Trait\HasSender;


trait SendsRequests
{
    use HasSender;
    use HasPipeline;

    public function send(Request $request): PendingRequest
    {
        $pendingRequest = $this->createPendingRequest($request);

        $requestMiddleware = $pendingRequest->getRequest()->middlewares();
        $connectorMiddleware = $pendingRequest->getConnector()->middlewares();

        return $this->getPipeline()
            ->pipe(array_merge($connectorMiddleware, $requestMiddleware))
            ->send($pendingRequest)
            ->then($this->handleRequest());
    }

    protected function handleRequest(): \Closure
    {
        return function (PendingRequest $pendingRequest) {

            try {

                $response = $pendingRequest->getConnector()->client()->request(
                    method: $pendingRequest->getMethod()->value,
                    uri: $pendingRequest->getUrl(),
                    parameters: $pendingRequest->getParameters(),
                    files: $pendingRequest->getFiles(),
                    server: $pendingRequest->getServer(),
                    content: $pendingRequest->getContent(),
                    changeHistory: $pendingRequest->isChangeHistory()
                );

                $pendingRequest->setCrawler($response);

                return $this->executeActions($pendingRequest) ?: $pendingRequest;
            } catch (\Exception $e) {
                throw $e;
//                throw new SeleniumRequestException("请求失败: " . $e->getMessage(), 0, $e);
            }
        };
    }

    protected function executeActions(PendingRequest $pendingRequest):?PendingRequest
    {
        return $pendingRequest->getRequest()->actions($pendingRequest);
    }

    public function createPendingRequest(Request $request): PendingRequest
    {
        return new PendingRequest($this, $request);
    }
}
