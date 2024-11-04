<?php

namespace App\Filament\Resources\PageVisitsResource\Widgets;

use App\Models\PageVisits;
use Filament\Widgets\Widget;

class GeolocationChart extends Widget
{
    protected static ?string $heading = '访问分布图';

    protected static string $view = 'filament.widgets.geolocation-chart';

    protected int | string | array $columnSpan = 'full';

    public function getData(): string
    {
        $countryData = PageVisits::selectRaw('country, COUNT(*) as count')
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('count')
            ->get();

        $cityData = PageVisits::selectRaw('city, country, COUNT(*) as count, AVG(latitude) as lat, AVG(longitude) as lng')
            ->whereNotNull('city')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->groupBy('city', 'country')
            ->orderByDesc('count')
            ->get();

        return json_encode([
            'countries' => $countryData->map(function ($item) {
                return [
                    'name' => $item->country,
                    'value' => $item->count,
                ];
            }),
            'cities' => $cityData->map(function ($item) {
                return [
                    'name' => $item->city,
                    'value' => [$item->lng, $item->lat, $item->count],
                    'country' => $item->country
                ];
            })
        ]);
    }

    protected function getViewData(): array
    {
        return [
            'chartData' => $this->getData(),
        ];
    }
}
