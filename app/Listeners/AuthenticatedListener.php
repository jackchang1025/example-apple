<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Weijiajia\SaloonphpAppleClient\Events\Authenticated\AuthenticatedEvent;

class AuthenticatedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AuthenticatedEvent $event): void
    {

        // $cacheKey = sprintf(
        //     '%s_%s',
        //     $event->apple->getConfig()->get('cache.prefix.authenticate', 'authenticate'),
        //     $event->apple->getAccount()->getSessionId()
        // );

        // Cache::put(
        //     $cacheKey,
        //     $event->apple->getApiResources()->getIcloudResource()->getAuthenticationResource()->getAuthenticate()
        // );
    }
}
