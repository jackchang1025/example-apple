<?php

namespace App\Selenium\AppleClient;

use App\Selenium\Connector;
use App\Selenium\ConnectorFactory;

class AppleConnectorFactory implements ConnectorFactory
{

    public function create(mixed $session = null): Connector
    {
        $connector = new AppleConnector();

        $connector->setSession($session);

        return $connector;
    }
}
