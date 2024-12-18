<?php

namespace Modules\AppleClient\Service\Trait;

use Saloon\Http\PendingRequest;

trait HasPipelineExists
{
    public function requestPipelineExists(PendingRequest $pendingRequest,string $name): bool
    {
        $pipes = $pendingRequest->getConnector()
            ->middleware()->getRequestPipeline()->getPipes();

        foreach ($pipes as $pipe) {
            if ($pipe->name === $name) {
                return true;
            }
        }

        return false;
    }

    public function responsePipelineExists(PendingRequest $pendingRequest,string $name): bool
    {
        $pipes = $pendingRequest->getConnector()
            ->middleware()->getResponsePipeline()->getPipes();

        foreach ($pipes as $pipe) {
            if ($pipe->name === $name) {
                return true;
            }
        }

        return false;
    }
}
