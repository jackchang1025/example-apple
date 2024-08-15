<?php

namespace App\Apple\WebAnalytics;

use Illuminate\Config\Repository;

class Ip extends Repository
{
    public function getStatus()
    {
        return $this->get('status');
    }

    public function getCountry()
    {
        return $this->get('country');
    }

    public function getCity()
    {
        return $this->get('city');
    }

    public function getRegion()
    {
        return $this->get('region');
    }

    public function getLatitude()
    {
        return $this->get('latitude');
    }

    public function getLongitude()
    {
        return $this->get('longitude');
    }

    public function getTimeZone()
    {
        return $this->get('timezone');
    }

    public function getCountryCode()
    {
        return $this->get('countryCode');
    }

    public function getQuery()
    {
        return $this->get('query');
    }

    public function isSuccess(): bool
    {
        return $this->getStatus() === 'success';
    }

    public function getMessage(): ?string
    {
        return $this->get('message');
    }
}
