<?php

use Illuminate\Support\Facades\Cache;

$key = time();

test('example', function () use ($key) {
   
    $lock = Cache::lock($key, seconds: 60);
    

    expect($lock->get())->toBeTrue();
    
});

it('测试锁的释放', function () use ($key) {

    $lock2 = Cache::lock($key, seconds: 60);

    expect($lock2->get())->toBeFalse();
});
