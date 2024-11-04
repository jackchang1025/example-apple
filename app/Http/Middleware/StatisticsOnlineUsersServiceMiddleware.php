<?php

namespace App\Http\Middleware;

use App\Apple\WebAnalytics\OnlineUsersService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        try {
            $name = $request->route()?->getName() ?? $request->path();
            $this->onlineUsersService->recordVisit($name, $request->session()->getId());
        } catch (\Exception $e) {
            // 记录错误，但不中断请求
            Log::error("Failed to record online user visit: {$e}");
        }

        return $response;
    }
}
