<?php

namespace App\Http\Middleware;

use App\Models\SecuritySetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlackListIpsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = SecuritySetting::first();

        if (!$settings) {
            return $next($request);
        }

        //IP 黑名单
        if (!empty($settings->configuration['blacklist_ips']) && in_array($request->ip(), $settings->configuration['blacklist_ips'])) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}
