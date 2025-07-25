<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// 每分钟执行一次分析任务
Schedule::command('analytics:generate-online-users')->everyMinute();

// 每小时执行一次手机号验证任务
Schedule::command('phone:validate')
    ->hourly()
    ->withoutOverlapping() // 防止重复执行
    ->runInBackground() // 在后台运行
    ->appendOutputTo(storage_path('logs/phone-validation.log')); // 记录输出日志
