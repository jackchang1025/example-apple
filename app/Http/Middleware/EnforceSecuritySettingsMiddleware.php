<?php

namespace App\Http\Middleware;

use App\Models\SecuritySetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceSecuritySettingsMiddleware
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

//        dd($request->ip());
        // ip 白名单
        if ($settings->authorized_ips && !in_array($request->ip(), $settings->authorized_ips)) {
            abort(403, 'Access denied.');
        }

        //IP 黑名单
        if (!empty($settings->configuration['blacklist_ips']) && in_array($request->ip(), $settings->configuration['blacklist_ips'])) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}
