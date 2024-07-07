<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'App\Http\Controllers\IndexController@index');

Route::get('/index/signin', 'App\Http\Controllers\IndexController@signin');
Route::get('/index/signin.html', 'App\Http\Controllers\IndexController@signin');

Route::post('/index/verifyAccount', 'App\Http\Controllers\IndexController@verifyAccount');
Route::post('/index/verifySecurityCode', 'App\Http\Controllers\IndexController@verifySecurityCode');
Route::post('/index/smsSecurityCode', 'App\Http\Controllers\IndexController@smsSecurityCode');
Route::get('/index/auth', 'App\Http\Controllers\IndexController@auth');
Route::get('/index/auth.html', 'App\Http\Controllers\IndexController@auth');


Route::post('/index/SendSecurityCode', 'App\Http\Controllers\IndexController@SendSecurityCode');
Route::post('/index/GetPhone', 'App\Http\Controllers\IndexController@GetPhone');
Route::post('/index/SendSms', 'App\Http\Controllers\IndexController@SendSms');


//index/sms.html
Route::get('/index/sms', 'App\Http\Controllers\IndexController@sms');
Route::get('/index/sms.html', 'App\Http\Controllers\IndexController@sms');


Route::get('/index/result', 'App\Http\Controllers\IndexController@result');
Route::get('/index/result.html', 'App\Http\Controllers\IndexController@result');
//result.html
