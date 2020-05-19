<?php

namespace Osiset\BasicShopifyAPI\Contracts;

/**
 * Reprecents basic time handling for getting and sleeping.
 * Based on spatie/guzzle-rate-limiter-middleware
 */
interface TimeDeferrer
{
    /**
     * Get the current timestamp.
     *
     * @return int
     */
    public function getCurrentTime(): int;

    /**
     * Sleep for a number of mircoseconds.
     *
     * @param int $microseconds
     *
     * @return void
     */
    public function sleep(int $microseconds): void;
}
