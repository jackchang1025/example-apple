<?php

namespace App\Http\Middleware;

use App\Apple\Apple;
use App\Apple\Service\Exception\UnauthorizedException;
use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UnauthorizedMiddleware
{
    public function __construct(protected Apple $apple,protected Container $container)
    {
    }


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

        $account = $this->apple->getAppleIdConnector()
            ->getRepositories()
            ->get('account');

        // 获取用户信息
        if (empty($account)) {
            throw new UnauthorizedException('Unauthorized',403);
        }
        $request->setUserResolver(fn() => $account);

        return $next($request);
    }
}
