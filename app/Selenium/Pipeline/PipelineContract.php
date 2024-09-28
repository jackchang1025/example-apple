<?php

namespace App\Selenium\Pipeline;

use Closure;

interface PipelineContract
{
    /**
     * Set the traveler object being sent on the pipeline.
     *
     * @param mixed $passable
     * @return $this
     */
    public function send(mixed $passable): self;

    /**
     * Set the stops of the pipeline.
     *
     * @param mixed $pipes
     * @return $this
     */
    public function through(mixed $pipes): self;

    /**
     * Set the method to call on the stops.
     *
     * @param string $method
     * @return $this
     */
    public function via(string $method): self;

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param  \Closure  $destination
     * @return mixed
     */
    public function then(Closure $destination): mixed;
}
