<?php

namespace App\Http\Middleware;

use App\Models\SecuritySetting;
use Closure;
use Illuminate\Http\Request;



class LocalMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $settings = $this->getSecuritySetting();

        if (!empty($settings?->configuration['language'])) {

            app()->setLocale($settings->configuration['language']);
        }

        return $next($request);
    }

    public function getSecuritySetting(): ?SecuritySetting
    {
        return SecuritySetting::first();
    }
}
