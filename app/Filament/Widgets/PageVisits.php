<?php

namespace App\Filament\Widgets;

use App\Apple\WebAnalytics\OnlineUsersService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PageVisits extends BaseWidget
{

    protected function getStats(): array
    {
        $onlineUsersService = app(OnlineUsersService::class);

        $data = $onlineUsersService->getOnlineAllPages();

        return [
            Stat::make('总访问量', \App\Models\PageVisits::count())->description('所有页面总访问量'),
            Stat::make('验证账号', $data->get('验证账号', 0))->description('在线人数'),
            Stat::make('授权', $data->get('授权', 0))->description('在线人数'),
            Stat::make('授权成功', $data->get('授权成功', 0))->description('在线人数'),

        ];
    }

    protected static ?string $pollingInterval = '5s';
}
