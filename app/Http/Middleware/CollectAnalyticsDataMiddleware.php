<?php

namespace App\Http\Middleware;

use App\Apple\WebAnalytics\WebAnalyticsCollector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CollectAnalyticsDataMiddleware
{

    public function __construct(protected WebAnalyticsCollector $collector)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 只在 GET 请求时收集数据
        if ($request->isMethod('get')) {
            $this->collector->collectData($request);
        }

        return $response;
    }
}
