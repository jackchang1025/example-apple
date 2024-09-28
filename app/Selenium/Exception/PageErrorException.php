<?php

namespace App\Selenium\Exception;

use App\Selenium\Page\Page;
use Facebook\WebDriver\WebDriverElement;

class PageErrorException extends SeleniumException
{

    public function __construct(
        public Page $page,
        public WebDriverElement $element,
        string $message = '',
        int $code = 0,
        \Throwable $previous = null
    )
    {
        parent::__construct(
            $message,
            $code,
            $previous
        );
    }
}
