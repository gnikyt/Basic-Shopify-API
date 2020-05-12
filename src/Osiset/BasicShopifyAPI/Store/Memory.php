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
     * The data container.
     *
     * @var array
     */
    protected $container = [];

    /**
     * {@inheritDoc}
     */
    public function get(array $options = []): array
    {
        return $this->container;
    }

    /**
     * {@inheritDoc}
     */
    public function set(array $values, array $options = []): void
    {
        $this->container = $values;
    }

    /**
     * {@inheritDoc}
     */
    public function push($value, array $options = []): void
    {
        // Set the value as first element, cut values off at 2 entrys for current and previous
        array_unshift($this->container, $value);
        $this->container = array_slice($this->container, 0, 2);
    }
}
