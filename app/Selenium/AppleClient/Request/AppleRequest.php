<?php

namespace App\Selenium\AppleClient\Request;

use App\Selenium\AppleClient\Page\ApplePage;
use App\Selenium\PendingRequest;
use App\Selenium\Request\Method;
use App\Selenium\Request\Request;

class AppleRequest extends Request
{

    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return 'https://www.apple.com/';
    }

    public function actions(PendingRequest $pendingRequest): ?PendingRequest
    {
        $connector = $pendingRequest->getConnector();

        $client = $pendingRequest->getConnector()->client();

        $page = new ApplePage($connector);

        $pendingRequest->setPage($page);

        // 禁用网络捕获
//        $devTools->execute('Network.disable');

//        $logs = $webDriver->manage()->getLog('performance');
//
//
//        foreach ($logs as $log) {
//            $logEntry = json_decode($log['message'], true, 512, JSON_THROW_ON_ERROR);
//
//            $message = $logEntry['message'];
//
//            // 过滤网络请求相关的事件
//            if ($message['method'] === 'Network.responseReceived') {
//                var_dump($message);
//            }
//        }

        return $pendingRequest;
    }
}
