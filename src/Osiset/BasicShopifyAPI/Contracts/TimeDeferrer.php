<?php

namespace Osiset\BasicShopifyAPI\Contracts;

/**
 * Reprecents basic time handling for getting and sleeping.
 * Based on spatie/guzzle-rate-limiter-middleware.
 */
interface TimeDeferrer
{
    /**
     * Get the current timestamp with microseconds.
     *
     * @return float
     */
    public function getCurrentTime(): float;

    /**
     * Sleep for a number of microseconds.
     *
     * @param float $microseconds
     *
     * @return void
     */
    public function sleep(float $microseconds): void;
}
