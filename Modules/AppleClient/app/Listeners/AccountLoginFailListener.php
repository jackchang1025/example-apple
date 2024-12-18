<?php

namespace Modules\AppleClient\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\AppleClient\Events\AccountLoginFailEvent;

class AccountLoginFailListener
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
    public function handle(AccountLoginFailEvent $event): void
    {
//        $event->account->model()?->logs()->create(['action' => '登陆', 'description' => '登陆失败']);
    }
}
