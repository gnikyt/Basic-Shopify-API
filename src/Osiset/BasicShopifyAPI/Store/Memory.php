<?php

namespace Osiset\BasicShopifyAPI\Store;

use Osiset\BasicShopifyAPI\Contracts\TimeStorer;

/**
 * In-memory storage for timestamps used by rate limit middleware.
 */
class Memory implements TimeStorer
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
    public function set(int $timestamp, array $options = [])
    {
        $this->timestamps[] = $timestamp;
    }
}
