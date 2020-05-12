<?php

namespace Osiset\BasicShopifyAPI\Contracts;

/**
 * Reprecents timestampt storage.
 * Based on spatie/guzzle-rate-limiter-middleware
 */
interface TimeStorer
{
    /**
     * Get the previous timestamp values.
     *
     * @param array $options Optional options to pass through.
     *
     * @return array
     */
    public function get(array $options = []): array;

    /**
     * Set the timestamp values.
     *
     * @param array $timestamps The timestamps to set.
     * @param array $options    Optional options to pass through.
     *
     * @return void
     */
    public function set(array $timestamps, array $options = []): void;
}
