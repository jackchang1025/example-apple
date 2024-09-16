<?php

namespace App\Filament\Widgets;

use App\Apple\WebAnalytics\OnlineUsersService;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Log;
use App\Apple\WebAnalytics\Enums\Route;

class PageVisits extends BaseWidget
{
    use HasWidgetShield;

    protected static ?string $heading = '在线用户统计';

    public static function getHeading(): string
    {
        return '在线用户统计';
    }

    protected function getStats(): array
    {
        try {
            $onlineUsersService = app(OnlineUsersService::class);

            $totalVisits = \App\Models\PageVisits::select('ip_address')
                ->distinct()
                ->count('ip_address');

            $onlineUsersServiceData = $onlineUsersService->getOnlineCountForAllRoutes([
                '验证账号',
                '授权',
                '授权成功',
            ]);

            return [
                Stat::make('总访问量', $totalVisits)
                    ->description('所有用户总访问量'),
                Stat::make('验证账号', $onlineUsersServiceData->get('验证账号')?->count())
                    ->description('在线人数'),
                Stat::make('授权', $onlineUsersServiceData->get('授权')?->count())
                    ->description('在线人数'),
                Stat::make('授权成功', $onlineUsersServiceData->get('授权成功')?->count())
                    ->description('在线人数'),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get online users stats: {$e}");
            return [
                Stat::make('错误', '数据加载失败')
                    ->description('请检查日志'),
            ];
        }
    }

    protected static ?string $pollingInterval = '3s';
}
