<?php

namespace App\Http\Middleware;

use App\Models\SecuritySetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BlackListIpsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $settings = $this->getSecuritySetting();

        //IP 黑名单
        if (!empty($settings->configuration['blacklist_ips']) && in_array(
                $request->ip(),
                $settings->configuration['blacklist_ips'],
                true
            )) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }

    public function getSecuritySetting(): SecuritySetting
    {
        return Cache::remember(key: 'security_setting',ttl: 0,callback: function (): SecuritySetting{
            return SecuritySetting::firstOrNew(['configuration'=>[]]);
        });
    }
}
