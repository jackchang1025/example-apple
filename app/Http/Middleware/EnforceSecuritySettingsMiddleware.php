<?php

namespace App\Http\Middleware;

use App\Models\SecuritySetting;
use Closure;
use Illuminate\Http\Request;

class EnforceSecuritySettingsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $settings = SecuritySetting::first();

        if (!$settings) {
            return $next($request);
        }

        // ip 白名单
        if ($settings->authorized_ips && !in_array($request->ip(), $settings->authorized_ips, true)) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}
