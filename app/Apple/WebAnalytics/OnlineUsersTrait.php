<?php

namespace App\Apple\WebAnalytics;

use App\Apple\WebAnalytics\Enums\Route;
use Illuminate\Support\Collection;

trait OnlineUsersTrait
{
    abstract public function getOnlineAllPages(): Collection;

    public function getOnlineCountForRoute(string $name): Collection
    {
        return $this->getOnlineAllPages()
        ->only(Route::getRouteValues($name))

        /**
         * [
         *      'auth_page' => [
         *          '127.0.0.1' => 1717232323,
         *          '127.0.0.2' => 1717232323,
         *      ],
         *      'verify_page' => [
         *          '127.0.0.1' => 1717232323,
         *          '127.0.0.3' => 1717232323,
         *      ],
         * ]
         */

        //为了防止不同路由在线用户重复，需要去掉重复的在线用户 例如：授权有多个路由页面，需要去掉页面中重复的在线用户
        // 我们需要去掉 127.0.0.1 在 auth_page 和 verify_page 中都存在，所以需要去掉
        //然后统计每个路由的在线用户数量

        // 将二维数组扁平化，并保留用户ID
        ->flatMap(function (Collection $users) {
            return $users;
        });
    }

    /**
     * 获取多个路由的在线用户数量，并处理重复用户
     *
     * @param array $routeNames 需要统计的路由名称数组
     * @return Collection 返回一个集合，键为路由名称，值为该路由的在线用户集合
     */
    public function getOnlineCountForAllRoutes(array $routeNames): Collection
    {
        // 初始化结果集合和用户到路由的映射数组
        $result = collect();
        $userToRoute = [];

        // 确保路由名称不重复
        $routeNames = array_unique($routeNames);

        // 遍历每个路由名称
        foreach ($routeNames as $routeName) {
            // 获取当前路由的在线用户
            $usersForRoute = $this->getOnlineCountForRoute($routeName);

            if ($usersForRoute->isEmpty()) {
                // 如果当前路由没有在线用户，则跳过
                $result->put($routeName, collect());
                continue;
            }

            // 遍历当前路由的每个用户
            foreach ($usersForRoute as $userId => $timestamp) {
                // 检查用户是否已经在之前的路由中出现过
                if (isset($userToRoute[$userId])) {
                    $previousRoute = $userToRoute[$userId];

                    // 如果用户在之前的路由中出现过，从该路由中移除该用户
                    if ($result->has($previousRoute)) {
                        $result[$previousRoute]->forget($userId);
                    }
                }

                // 将用户添加到当前路由
                // 如果当前路由不存在，会自动创建一个新的集合
                $result->put($routeName, $result->get($routeName, collect())->put($userId, $timestamp));

                // 更新用户对应的路由
                $userToRoute[$userId] = $routeName;
            }
        }

        return $result;
    }
}
