<?php

namespace App\Http\Middleware;

use App\Apple\WebAnalytics\OnlineUsersService;
use App\Apple\WebAnalytics\WebAnalyticsCollector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StatisticsOnlineUsersServiceMiddleware
{
    public function __construct(protected OnlineUsersService $onlineUsersService,)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $name = $request->route()?->getName() ?? $request->path();
        $this->onlineUsersService->recordVisit($name, $request->session()->getId());

        return $response;
    }
}
