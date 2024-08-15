<?php

namespace App\Console\Commands;

use App\Apple\WebAnalytics\Enums\Route;
use App\Apple\WebAnalytics\OnlineUsersService;
use App\Models\PageVisits;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class GenerateOnlineUsers extends Command
{
    /**
     * Execute the console command.
     */
    protected $signature = 'analytics:generate-online-users';
    protected $description = 'Generate fake online users data in Redis';

    public function handle(OnlineUsersService $onlineUsersService,Container $container)
    {
        $routes = Route::getAllValues();

        $redis = $container->get('redis');

        foreach ($routes as $route) {

            $redis->del($onlineUsersService->formatKey($route));  // 清除旧数据

            $onlineUsers = rand(1, 100);  // 随机生成 1-100 的在线用户数

            // 为每个用户生成一个随机的 session ID 和时间戳
            for ($i = 0; $i < $onlineUsers; $i++) {
                $sessionId = Str::random(40);

                $onlineUsersService->recordVisit($route, $sessionId);
            }
        }

        $this->info('Fake online users data generated successfully.');
    }
}
