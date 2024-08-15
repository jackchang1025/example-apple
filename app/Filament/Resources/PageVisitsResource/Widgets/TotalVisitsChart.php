<?php

namespace App\Filament\Resources\PageVisitsResource\Widgets;

use App\Models\PageVisits;
use Filament\Widgets\ChartWidget;

class TotalVisitsChart extends ChartWidget
{
    protected static ?string $heading = '总访问量';

    protected int | string | array $columnSpan = 'full';
    protected function getData(): array
    {
        $data = PageVisits::select([
            PageVisits::raw('DATE(created_at) as date'),
            PageVisits::raw('COUNT(*) as count')
        ])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Visits',
                    'data' => $data->pluck('count'),
                ],
            ],
            'labels' => $data->pluck('date'),
        ];
    }
    protected function getOptions(): ?array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
