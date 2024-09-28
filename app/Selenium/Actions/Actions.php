<?php

namespace App\Selenium\Actions;

use App\Selenium\Page\Page;
use Facebook\WebDriver\WebDriverAction;

abstract class Actions implements WebDriverAction
{
    public function __construct(protected Page $page)
    {
    }
}
