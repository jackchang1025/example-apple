<?php

namespace App\Selenium\Helpers;


use App\Selenium\PendingRequest;

class BootPlugins
{
    /**
     * Boot the plugins
     * @param PendingRequest $pendingRequest
     * @return PendingRequest
     * @throws \ReflectionException
     */
    public function __invoke(PendingRequest $pendingRequest): PendingRequest
    {
        $connector = $pendingRequest->getConnector();
        $request = $pendingRequest->getRequest();

        $connectorTraits = Helpers::classUsesRecursive($connector);
        $requestTraits = Helpers::classUsesRecursive($request);

        foreach ($connectorTraits as $connectorTrait) {
            Helpers::bootPlugin($pendingRequest, $connector, $connectorTrait);
        }

        foreach ($requestTraits as $requestTrait) {
            Helpers::bootPlugin($pendingRequest, $request, $requestTrait);
        }

        return $pendingRequest;
    }
}
