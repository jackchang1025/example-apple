<?php

namespace App\Selenium;

interface ConnectorFactory
{
    public function create(mixed $session = null): Connector;
}
