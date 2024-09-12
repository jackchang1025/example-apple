<?php

namespace App\Apple\Service\Client;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;


readonly class ClientFactory
{

    public function __construct(
        private HttpFactory $http
    ) {
    }


    public function create(array $options = []): PendingRequest
    {
        /**
         * @var PendingRequest $client
         */
        $client = $this->http
            ->withOptions($options);

        return $client;
    }

}
