<?php

namespace App\Services;
use Psr\EventDispatcher\EventDispatcherInterface;
use Illuminate\Contracts\Events\Dispatcher;

class LaravelEventDispatcher implements EventDispatcherInterface
{

    public function __construct(private Dispatcher $laravelDispatcher)
    {
    }

    public function dispatch(object $event): object
    {
        $this->laravelDispatcher->dispatch($event);
        return $event;
    }
}