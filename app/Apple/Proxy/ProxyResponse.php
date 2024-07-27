<?php

namespace App\Apple\Proxy;

use Illuminate\Config\Repository;

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
}
