<?php

namespace App\Selenium\Exception;


use App\Selenium\Page\Page;

class PageException extends SeleniumException
{
    public function __construct(protected Page $page,string $message = "")
    {
        parent::__construct($message);
    }

    public function getPage(): Page
    {
        return $this->page;
    }
}
