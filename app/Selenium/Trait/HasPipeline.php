<?php

namespace App\Selenium\Trait;

use App\Selenium\Pipeline\Pipeline;
use App\Selenium\Pipeline\PipelineContract;

trait HasPipeline
{
    protected ?PipelineContract $pipeline = null;

    public function setPipeline(?PipelineContract $pipeline): static
    {
        $this->pipeline = $pipeline;
        return $this;
    }

    public function getPipeline(): PipelineContract
    {
        return $this->pipeline ??= new Pipeline;
    }

    public function then()
    {

    }
}
