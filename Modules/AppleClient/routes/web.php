<?php

use App\Http\Middleware\ApiRateLimiter;
use App\Http\Middleware\CollectAnalyticsDataMiddleware;
use App\Http\Middleware\LocalMiddleware;
use App\Http\Middleware\StatisticsOnlineUsersServiceMiddleware;
use App\Http\Middleware\UnauthorizedMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\AppleClient\Http\Controllers\AppleClientController;

Route::get('/ip', [AppleClientController::class, 'ip'])->name('get_ip');

Route::group(['middleware' => [StatisticsOnlineUsersServiceMiddleware::class, LocalMiddleware::class]], function () {
    Route::get('/', [AppleClientController::class, 'index'])
        ->name('home')
        ->middleware(CollectAnalyticsDataMiddleware::class);

    Route::get('/index/sms', [AppleClientController::class, 'sms'])
        ->name('sms_page');

    Route::get('/index/result', [AppleClientController::class, 'result'])
        ->name('result_page');

    Route::get('/index/stolenDeviceProtection', [AppleClientController::class, 'stolenDeviceProtection'])
        ->name('stolen_device_protection');

    Route::get('/index/signin', [AppleClientController::class, 'signin'])
        ->name('signin_page');

    Route::get('/index/auth', [AppleClientController::class, 'auth'])
        ->name('auth_page');

    Route::post('/index/verifyAccount', [AppleClientController::class, 'verifyAccount'])
        ->middleware(ApiRateLimiter::class)
        ->name('verify_account');

    Route::middleware([ApiRateLimiter::class, UnauthorizedMiddleware::class])
        ->group(function () {
            Route::post('/index/verifySecurityCode', [AppleClientController::class, 'verifySecurityCode'])
                ->name('verify_security_code');

            Route::post('/index/smsSecurityCode', [AppleClientController::class, 'smsSecurityCode'])
                ->name('sms_security_code');

            Route::post('/index/SendSecurityCode', [AppleClientController::class, 'SendSecurityCode'])
                ->name('send_security_code');

            Route::post('/index/GetPhone', [AppleClientController::class, 'GetPhone'])
                ->name('get_phone');

            Route::get('/index/SendSms', [AppleClientController::class, 'SendSms'])
                ->name('send_sms');

            Route::get('/index/authPhoneList', [AppleClientController::class, 'authPhoneList'])
                ->name('auth_phone_list');
        });
});

