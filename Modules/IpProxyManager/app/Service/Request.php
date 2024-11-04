<?php

namespace Modules\IpProxyManager\Service;

abstract class Request extends \Saloon\Http\Request
{
    /**
     * @param BaseDto $dto
     */
    public function __construct(public BaseDto $dto)
    {
    }

    public function getDto(): BaseDto
    {
        return $this->dto;
    }
}
