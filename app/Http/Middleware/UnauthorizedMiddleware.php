<?php

namespace App\Http\Middleware;

use App\Apple\Exception\UnauthorizedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UnauthorizedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @throws UnauthorizedException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (empty($request->input('Guid'))){
            throw new UnauthorizedException('Unauthorized',401);
        }

        return $next($request);
    }
}
