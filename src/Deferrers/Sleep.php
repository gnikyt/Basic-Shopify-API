<?php

namespace Osiset\BasicShopifyAPI\Deferrers;

use Osiset\BasicShopifyAPI\Contracts\TimeDeferrer;

/**
 * Base time deferrer implementation.
 * Based on spatie/guzzle-rate-limiter-middleware.
 */
class Sleep implements TimeDeferrer
{
    /**
     * {@inheritdoc}
     */
    public function getCurrentTime(): float
    {
        return microtime(true) * 1000000;
    }

    /**
     * {@inheritdoc}
     */
    public function sleep(float $microseconds): void
    {
        usleep((int) $microseconds);
    }
}
