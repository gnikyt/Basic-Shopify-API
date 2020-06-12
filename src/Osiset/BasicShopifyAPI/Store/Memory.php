<?php

namespace Osiset\BasicShopifyAPI\Store;

use Osiset\BasicShopifyAPI\Contracts\StateStorage;
use Osiset\BasicShopifyAPI\Session;

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
    public function all(): array
    {
        return $this->container;
    }

    /**
     * {@inheritDoc}
     */
    public function get(Session $session): array
    {
        $shop = $session->getShop();
        return isset($this->container[$shop]) ? $this->container[$shop] : [];
    }

    /**
     * {@inheritDoc}
     */
    public function set(array $values, Session $session): void
    {
        $this->container[$session->getShop()] = $values;
    }

    /**
     * {@inheritDoc}
     */
    public function push($value, Session $session): void
    {
        $shop = $session->getShop();
        if (!isset($this->container[$shop])) {
            $this->reset($session);
        }

        array_unshift($this->container[$shop], $value);
    }

    /**
     * {@inheritDoc}
     */
    public function reset(Session $session): void
    {
        $this->container[$session->getShop()] = [];
    }
}
