<?php

namespace App\Apple\WebAnalytics;

use App\Models\PageVisits;

class AnalyticsService
{
    public function __construct(protected OnlineUsersService $onlineUsersService)
    {
    }


    public function getVisitorsByTimeRange($startDate, $endDate): \Illuminate\Support\Collection
    {
        return PageVisits::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('created_at')
            ->get();
    }

    public function getTopPages($startDate, $endDate, $limit = 30): \Illuminate\Support\Collection
    {
        return PageVisits::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('uri')
            ->select('uri', PageVisits::raw('COUNT(*) as count'))
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    public function getOnlineCount(string $uri): int
    {
        return $this->onlineUsersService->getOnlineCount($uri);
    }
    public function getOnlineCounts(): int
    {
        return $this->onlineUsersService->getTotalOnlineCount();
    }

}
