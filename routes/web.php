<?php

use App\Http\Middleware\CollectAnalyticsDataMiddleware;
use App\Http\Middleware\UnauthorizedMiddleware;
use Illuminate\Support\Facades\Route;


Route::get('/ip', 'App\Http\Controllers\IndexController@ip');

Route::group(['middleware' => [CollectAnalyticsDataMiddleware::class]],function (){

    Route::get('/', 'App\Http\Controllers\IndexController@index')->name('/');
    Route::get('/index/sms', 'App\Http\Controllers\IndexController@sms')->name('sms');
    Route::get('/index/result', 'App\Http\Controllers\IndexController@result')->name('result');
    Route::get('/index/signin', 'App\Http\Controllers\IndexController@signin')->name('signin');
    Route::get('/index/auth', 'App\Http\Controllers\IndexController@auth')->name('auth');
});

Route::middleware(UnauthorizedMiddleware::class)->group(function (){

    Route::post('/index/verifyAccount', 'App\Http\Controllers\IndexController@verifyAccount')
        ->withoutMiddleware(UnauthorizedMiddleware::class);

    Route::post('/index/verifySecurityCode', 'App\Http\Controllers\IndexController@verifySecurityCode');
    Route::post('/index/smsSecurityCode', 'App\Http\Controllers\IndexController@smsSecurityCode');

    Route::post('/index/SendSecurityCode', 'App\Http\Controllers\IndexController@SendSecurityCode');
    Route::post('/index/GetPhone', 'App\Http\Controllers\IndexController@GetPhone');
    Route::post('/index/SendSms', 'App\Http\Controllers\IndexController@SendSms');

    Route::get('/index/authPhoneList', 'App\Http\Controllers\IndexController@authPhoneList')
        ->name('auth_phone_list')
        ->middleware(CollectAnalyticsDataMiddleware::class);

});

//result.html
