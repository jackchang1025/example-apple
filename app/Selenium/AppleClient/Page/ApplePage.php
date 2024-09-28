<?php

namespace App\Selenium\AppleClient\Page;

use App\Selenium\Page\Page;
use Facebook\WebDriver\WebDriverBy;

class ApplePage extends Page
{

    public function defaultExceptionSelector(): WebDriverBy
    {
        return WebDriverBy::cssSelector('body.page-home.ac-nav-overlap.globalnav-scrim.globalheader-light.ribbon');
    }

    public function resolveRootElement(): WebDriverBy
    {
        return WebDriverBy::cssSelector('body.page-home.ac-nav-overlap.globalnav-scrim.globalheader-light.ribbon');
    }
}
