<?php

namespace App\Selenium\Trait;

use App\Selenium\Helpers\Helpers;
use Throwable;

trait HasRetry
{
    /**
     * @throws Throwable
     */
    public function retry($times, callable $callback, $sleepMilliseconds = 0.2, $when = null)
    {
        $attempts = 0;

        $backoff = [];

        if (is_array($times)) {
            $backoff = $times;

            $times = count($times) + 1;
        }

        beginning:
        $attempts++;
        $times--;

        try {
            return $callback($attempts);
        } catch (Throwable $e) {
            if ($times < 1 || ($when && ! $when($e))) {
                throw $e;
            }

            $sleepMilliseconds = $backoff[$attempts - 1] ?? $sleepMilliseconds;

            if ($sleepMilliseconds) {
                usleep(Helpers::value($sleepMilliseconds, $attempts, $e) * 1000);
            }

            goto beginning;
        }
    }
}
