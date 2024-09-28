<?php

namespace App\Selenium;

use Hyperf\Context\ApplicationContext;
use Hyperf\Logger\Logger;
use Hyperf\Logger\LoggerFactory;

class NetworkLogger
{
    private array $requests = [];
    private array $responses = [];

    public function getRequests(): array
    {
        return $this->requests;
    }

    public function getResponses(): array
    {
        return $this->responses;
    }

    public function parseNetworkLogs(array $logs, string $targetUrl): ?array
    {
        foreach ($logs as $log) {
            $logEntry = json_decode($log['message'], true);
            $message = $logEntry['message'];

            if ($message['method'] === 'Network.requestWillBeSent') {
                $this->handleRequest($message['params']);
            } elseif ($message['method'] === 'Network.responseReceived') {
                $this->handleResponse($message['params']);
            }
        }

        return $this->getResponseData($targetUrl);
    }

    private function handleRequest($params): void
    {
        ApplicationContext::getContainer()->get(LoggerFactory::class)->get()->info("----------------------------------handleRequest----------------------------------",$params);
        $this->requests[$params['requestId']] = [
            'url' => $params['request']['url'],
            'method' => $params['request']['method'],
            'headers' => $params['request']['headers']
        ];
    }

    private function handleResponse($params): void
    {
        ApplicationContext::getContainer()->get(LoggerFactory::class)->get()->info("----------------------------------handleResponse----------------------------------",$params);
        $this->responses[$params['requestId']] = [
            'status' => $params['response']['status'],
            'headers' => $params['response']['headers'],
            'mimeType' => $params['response']['mimeType']
        ];
    }

    public function getResponseData($targetUrl): ?array
    {
        foreach ($this->requests as $requestId => $request) {
            if ($request['url'] === $targetUrl && isset($this->responses[$requestId])) {
                return [
                    'request' => $request,
                    'response' => $this->responses[$requestId]
                ];
            }
        }
        return null;
    }
}
