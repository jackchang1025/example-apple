<?php

use Illuminate\Foundation\Testing\TestCase;

uses(TestCase::class);

test('example', function () {
    expect(true)->toBeTrue();
});

test('example lang', function () {

    expect(__('Hello world'))->toBe('Hello world');//App::isLocale('en')
});

test('example lang zh_CN', function () {

    App::setLocale('zh_CN');

    expect(__('apple.signin.title'))->toBe('登录 Apple ID');
});

test('example lang en', function () {

    App::setLocale('en');

    expect(__('apple.signin.title'))->toBe('Sign in with Apple ID');
});

