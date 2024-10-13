<?php

namespace Modules\IpProxyManager\Service;

use Carbon\Carbon;

class ProxyResponse
{
    public function __construct(
        public ?string $host = null,
        public ?int $port = null,
        public ?string $user = null,
        public ?string $password = null,
        public ?string $url = null,
        public ?Carbon $expireTime = null
    )
    {
    }
}
