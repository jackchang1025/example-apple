<?php

namespace App\Filament\Resources\PageVisitsResource\Widgets;

use App\Apple\WebAnalytics\OnlineUsersService;
use Filament\Widgets\ChartWidget;

class OnlineUsersChart extends ChartWidget
{

    protected static ?string $heading = '页面在线人数';

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = '5';

    protected function getData(): array
    {
        $onlineUsersService = app(OnlineUsersService::class);
        $data = $onlineUsersService->getOnlineCountsForAllPages((int)$this->filter);

        return [
            'datasets' => [
                [
                    'label' => 'Online Users',
                    'data' => array_values($data),
                    'backgroundColor' => '#36A2EB',
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            5 => 'Top 5',
            10 => 'Top 10',
            15 => 'Top 15',
            20 => 'Top 20',
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getPollingInterval(): ?string
    {
        return '3s';
    }
}
