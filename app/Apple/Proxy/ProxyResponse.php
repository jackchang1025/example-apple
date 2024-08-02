<?php

namespace App\Apple\Proxy;

use Illuminate\Config\Repository;
use Illuminate\Support\Carbon;

class ProxyResponse extends Repository
{
    public function getHost()
    {
        return $this->get('host');
    }

    public function getPort()
    {
        return $this->get('port');
    }

    public function getUrl()
    {
        return $this->get('url');
    }

    public function getUserName()
    {
        return $this->get('username');
    }

    public function getPassword()
    {
        return $this->get('password');
    }

    public function getAuth()
    {
        return $this->get('auth');
    }

    public function getExpire():?Carbon
    {
        return Carbon::parse($this->get('expire_time'));
    }

    public function getTimeToExpire(): int
    {
        return max(0, $this->getExpire()->getTimestamp() - Carbon::now()->getTimestamp());
    }
}
