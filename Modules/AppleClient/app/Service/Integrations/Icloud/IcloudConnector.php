<?php

namespace Modules\AppleClient\Service\Integrations\Icloud;

use Modules\AppleClient\Service\AppleClient;
use Modules\AppleClient\Service\Integrations\AppleConnector;
use Modules\AppleClient\Service\Integrations\Icloud\Resources\FamilyResources;
use Modules\AppleClient\Service\Integrations\Icloud\Resources\Resources;
use Saloon\Http\Auth\BasicAuthenticator;

class IcloudConnector extends AppleConnector
{
    public function __construct(
        AppleClient $apple,
        public readonly string $dsid = '',
        public readonly string $mmeAuthToken = '',
    ) {
        parent::__construct($apple);
    }

    protected function defaultAuth(): BasicAuthenticator
    {
        return new BasicAuthenticator($this->dsid, $this->mmeAuthToken);
    }

    public function resolveBaseUrl(): string
    {
        return 'https://setup.icloud.com';
    }

    public function getResources(): Resources
    {
        return new Resources($this);
    }

    public function getFamilyResources(): FamilyResources
    {
        return new FamilyResources($this);
    }
}
