<?php

namespace App\Apple\WebAnalytics;

use App\Models\PageVisits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class WebAnalyticsCollector
{

    /**
     */
    public function __construct(
        protected Agent $agent,
        protected OnlineUsersService $onlineUsersService,
        protected IpApi $ipApi
    ) {
    }

    public function collectData(Request $request): Model|PageVisits
    {
        $name = $request->route()->getName() ?? $request->path();
        $this->onlineUsersService->recordVisit($name, $request->session()->getId());

        $ipAddress = $request->ip();

        $geoData = $this->getGeoData($ipAddress);

        return PageVisits::firstOrCreate([
            'ip_address'  => $ipAddress,
        ],[
            'uri'         => $request->path(),
            'user_agent'  => $request->userAgent(),
            'name'        => $request->route()->getName(),
            'device_type' => $this->agent->device(),
            'browser'     => $this->agent->browser(),
            'platform'    => $this->agent->platform(),
            'country'     => $geoData['country'] ?? null,
            'city'        => $geoData['city'] ?? null,
            'latitude'    => $geoData['latitude'] ?? null,
            'longitude'   => $geoData['longitude'] ?? null,
        ]);
    }

    protected function getGeoData($ipAddress): array
    {
        try {
            $record = $this->ipApi->getIpInfo($ipAddress);

            return [
                'country'   => $record->getCountry(),
                'city'      => $record->getCity(),
                'latitude'  => $record->getLatitude(),
                'longitude' => $record->getLongitude(),
            ];
        } catch (ConnectionException $e) {
            // Log the error or handle it as appropriate
            return [];
        }
    }
}
