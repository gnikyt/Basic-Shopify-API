<?php

namespace Osiset\BasicShopifyAPI\Deferrers;

use Osiset\BasicShopifyAPI\Contracts\TimeDeferrer;

/**
 * Base time deferrer implementation.
 * Based on spatie/guzzle-rate-limiter-middleware
 */
class Sleep implements TimeDeferrer
{
    /**
     * {@inheritDoc}
     */
    public function getCurrentTime(): int
    {
        return (int) round(microtime(true) * 1000);
    }

    /**
     * {@inheritDoc}
     */
    public function sleep(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }
}
