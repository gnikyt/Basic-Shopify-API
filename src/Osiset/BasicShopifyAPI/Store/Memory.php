<?php

namespace Osiset\BasicShopifyAPI\Store;

use Osiset\BasicShopifyAPI\Contracts\StateStorage;
use Osiset\BasicShopifyAPI\Contracts\TimeStorer;

/**
 * In-memory storage for timestamps used by rate limit middleware.
 * Based on spatie/guzzle-rate-limiter-middleware
 */
class Memory implements StateStorage
{
    /**
     * The timestamps.
     *
     * @var array
     */
    protected $timestamps = [];

    /**
     * {@inheritDoc}
     */
    public function get(array $options = []): array
    {
        return $this->timestamps;
    }

    /**
     * {@inheritDoc}
     */
    public function set(array $values, array $options = [])
    {
        $this->timestamps = $values;
    }

    /**
     * {@inheritDoc}
     */
    public function push($value, array $options = []): void
    {
        // Set the value as first element, cut values off at 2 entrys for current and previous
        array_unshift($this->timestamps, $value);
        $this->timestamps = array_slice($this->timestamps, 0, 2);
    }
}
