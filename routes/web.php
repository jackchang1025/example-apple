<?php

use App\Http\Middleware\ApiRateLimiter;
use App\Http\Middleware\CollectAnalyticsDataMiddleware;
use App\Http\Middleware\StatisticsOnlineUsersServiceMiddleware;
use App\Http\Middleware\UnauthorizedMiddleware;
use Illuminate\Support\Facades\Route;


Route::get('/ip', 'App\Http\Controllers\IndexController@ip')->name('get_ip');

Route::group(['middleware' => [StatisticsOnlineUsersServiceMiddleware::class]], function () {
    Route::get('/', 'App\Http\Controllers\IndexController@index')
        ->name('home')
        ->middleware(CollectAnalyticsDataMiddleware::class);

    Route::get('/index/sms', 'App\Http\Controllers\IndexController@sms')
        ->name('sms_page');

    Route::get('/index/result', 'App\Http\Controllers\IndexController@result')
        ->name('result_page');

    Route::get('/index/signin', 'App\Http\Controllers\IndexController@signin')
        ->name('signin_page');

    Route::get('/index/auth', 'App\Http\Controllers\IndexController@auth')
        ->name('auth_page');

    Route::post('/index/verifyAccount', 'App\Http\Controllers\IndexController@verifyAccount')
        ->middleware(ApiRateLimiter::class)
        ->name('verify_account');

    Route::middleware([ApiRateLimiter::class, UnauthorizedMiddleware::class])
        ->group(function () {
            Route::post('/index/verifySecurityCode', 'App\Http\Controllers\IndexController@verifySecurityCode')
                ->name('verify_security_code');

            Route::post('/index/smsSecurityCode', 'App\Http\Controllers\IndexController@smsSecurityCode')
                ->name('sms_security_code');

            Route::post('/index/SendSecurityCode', 'App\Http\Controllers\IndexController@SendSecurityCode')
                ->name('send_security_code');

            Route::post('/index/GetPhone', 'App\Http\Controllers\IndexController@GetPhone')
                ->name('get_phone');

            Route::post('/index/SendSms', 'App\Http\Controllers\IndexController@SendSms')
                ->name('send_sms');

            Route::get('/index/authPhoneList', 'App\Http\Controllers\IndexController@authPhoneList')
                ->name('auth_phone_list');
        });
});
