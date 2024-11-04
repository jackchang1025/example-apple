<?php

namespace Tests;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // 声明静态属性
    public static string|null $sessionId = null;

    public static ?DesiredCapabilities $capabilities = null;
}
