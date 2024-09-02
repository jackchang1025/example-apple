<?php

namespace App\Http\Integrations\IpConnector\Responses;

use Saloon\Repositories\ArrayStore;

abstract class IpResponse extends ArrayStore
{
    abstract public function getCity():?string;
    abstract public function getAddr():?string;
    abstract public function getIp():?string;
    abstract public function cityCode():?string;
    abstract public function proCode():?string;

    abstract public function isChain():bool;
}
