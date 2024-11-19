<?php

namespace Modules\AppleClient\Service\Response;


use Illuminate\Support\Collection;
use Modules\AppleClient\Service\Helpers\PlistXmlParser;


trait XmlResponseHandler
{
    public function xmlToCollection(): Collection
    {
        return (new PlistXmlParser())->xmlParse($this->xml());
    }
}
