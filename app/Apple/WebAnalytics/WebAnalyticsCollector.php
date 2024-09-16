<?php

namespace App\Apple\WebAnalytics;

use App\Models\PageVisits;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

class WebAnalyticsCollector
{
    public function __construct(
        protected Agent $agent,
        protected IpApi $ipApi
    ) {
    }

    public function collectData(Request $request): PageVisits
    {
        $ipAddress = $request->ip();
        $geoData = $this->getCachedGeoData($ipAddress);

        return PageVisits::updateOrCreate(
            ['ip_address' => $ipAddress],
            $this->preparePageVisitData($request, $geoData)
        );
    }

    protected function getCachedGeoData(string $ipAddress): array
    {
        return Cache::remember("geo_data_{$ipAddress}", now()->addDays(30), function () use ($ipAddress) {
            try {
                $record = $this->ipApi->getIpInfo($ipAddress);
                return [
                    'country'   => $record->getCountry(),
                    'city'      => $record->getCity(),
                    'latitude'  => $record->getLatitude(),
                    'longitude' => $record->getLongitude(),
                ];
            } catch (\Exception $e) {
                // Log the error and return empty array
                Log::error("Failed to get geo data for IP: {$ipAddress}. Error: " . $e->getMessage());
                return [];
            }
        });
    }

    protected function preparePageVisitData(Request $request, array $geoData): array
    {
        return [
            'uri'         => $request->path(),
            'user_agent'  => $request->userAgent(),
            'name'        => $request->route()?->getName(),
            'device_type' => $this->agent->device(),
            'browser'     => $this->agent->browser(),
            'platform'    => $this->agent->platform(),
            'country'     => $geoData['country'] ?? null,
            'city'        => $geoData['city'] ?? null,
            'latitude'    => $geoData['latitude'] ?? null,
            'longitude'   => $geoData['longitude'] ?? null,
            'updated_at'   => Carbon::now(),
        ];
    }
}
