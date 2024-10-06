<?php

namespace App\Http\Middleware;

use App\Apple\Service\Apple;
use App\Apple\Service\AppleFactory;
use App\Apple\Service\Exception\UnauthorizedException;
use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UnauthorizedMiddleware
{
    public function __construct(protected AppleFactory $appleFactory,protected Container $container)
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
        if (empty($guid = $request->cookie('Guid'))){
            throw new UnauthorizedException('Unauthorized',401);
        }

//        $apple = $this->appleFactory->create($guid);
//
//        // 获取用户信息
//        $account = $apple->getUser()->getAccount();
//        if (empty($account)) {
//            throw new UnauthorizedException('Unauthorized',403);
//        }
//        $request->setUserResolver(fn() => $account);
//
//        //设置 Apple 实例
//        $request->attributes->set('apple', $apple);
//        $this->container->singleton(Apple::class, fn() => $apple);

        return $next($request);
    }
}
