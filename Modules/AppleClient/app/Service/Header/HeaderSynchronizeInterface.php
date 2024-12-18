<?php

namespace Modules\AppleClient\Service\Header;

use Saloon\Contracts\ArrayStore;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

interface HeaderSynchronizeInterface
{
    public function extractHeader(Response $response): Response;

    public function withHeader(PendingRequest $pendingRequest): PendingRequest;

    public function getHeaderRepositories(): ArrayStore;
}
