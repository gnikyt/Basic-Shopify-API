<?php

namespace Osiset\BasicShopifyAPI\Contracts;

/**
 * Reprecents basic state storage.
 * Based on spatie/guzzle-rate-limiter-middleware
 */
interface StateStorage
{
    /**
     * Get the values.
     *
     * @param array $options Optional options to pass through.
     *
     * @return array
     */
    public function get(array $options = []): array;

    /**
     * Set the values.
     *
     * @param array $values  The values to set.
     * @param array $options Optional options to pass through.
     *
     * @return void
     */
    public function set(array $values, array $options = []): void;

    /**
     * Set the values.
     *
     * @param mixed $value   The value to add.
     * @param array $options Optional options to pass through.
     *
     * @return void
     */
    public function push($value, array $options = []): void;
}
