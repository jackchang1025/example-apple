<?php

namespace Modules\AppleClient\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\AppleClient\Events\AccountBindPhoneFailEvent;
use Modules\AppleClient\Events\Authenticated\AuthenticatedEvent;
use Modules\AppleClient\Listeners\AccountBindPhoneFailListener;
use Modules\AppleClient\Events\AccountBindPhoneSuccessEvent;
use Modules\AppleClient\Listeners\AccountBindPhoneSuccessListener;
use Modules\AppleClient\Events\AccountLoginSuccessEvent;
use Modules\AppleClient\Listeners\AccountLoginSuccessListener;
use Modules\AppleClient\Events\AccountLoginFailEvent;
use Modules\AppleClient\Listeners\AccountLoginFailListener;
use Modules\AppleClient\Events\SendPhoneSecurityCodeFailEvent;
use Modules\AppleClient\Listeners\AuthenticatedListener;
use Modules\AppleClient\Listeners\SendPhoneSecurityCodeFailListener;
use Modules\AppleClient\Events\SendPhoneSecurityCodeSuccessEvent;
use Modules\AppleClient\Listeners\SendPhoneSecurityCodeSuccessListener;
use Modules\AppleClient\Events\SendVerificationCodeFailEvent;
use Modules\AppleClient\Listeners\SendVerificationCodeFailListener;
use Modules\AppleClient\Events\SendVerificationCodeSuccessEvent;
use Modules\AppleClient\Listeners\SendVerificationCodeSuccessListener;
use Modules\AppleClient\Events\VerifySecurityCodeFailEvent;
use Modules\AppleClient\Listeners\VerifySecurityCodeFailListener;
use Modules\AppleClient\Events\VerifySecurityCodeSuccessEvent;
use Modules\AppleClient\Listeners\VerifySecurityCodeSuccessListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        AccountBindPhoneFailEvent::class         => [
            AccountBindPhoneFailListener::class,
        ],
        AccountBindPhoneSuccessEvent::class      => [
            AccountBindPhoneSuccessListener::class,
        ],
        AccountLoginSuccessEvent::class          => [
            AccountLoginSuccessListener::class,
        ],
        AccountLoginFailEvent::class             => [
            AccountLoginFailListener::class,
        ],
        SendPhoneSecurityCodeFailEvent::class    => [
            SendPhoneSecurityCodeFailListener::class,
        ],
        SendPhoneSecurityCodeSuccessEvent::class => [
            SendPhoneSecurityCodeSuccessListener::class,
        ],
        SendVerificationCodeFailEvent::class     => [
            SendVerificationCodeFailListener::class,
        ],
        SendVerificationCodeSuccessEvent::class  => [
            SendVerificationCodeSuccessListener::class,
        ],
        VerifySecurityCodeFailEvent::class       => [
            VerifySecurityCodeFailListener::class,
        ],
        VerifySecurityCodeSuccessEvent::class    => [
            VerifySecurityCodeSuccessListener::class,
        ],

        AuthenticatedEvent::class => [
            AuthenticatedListener::class,
        ],

    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void
    {
        //
    }
}
