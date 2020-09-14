<?php

namespace Osiset\BasicShopifyAPI\Contracts;

use Osiset\BasicShopifyAPI\Session;

/**
 * Reprecents basic state storage.
 * Based on spatie/guzzle-rate-limiter-middleware.
 */
interface StateStorage
{
    /**
     * Get all container values.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Get the values.
     *
     * @param Session $session The shop session.
     *
     * @return array
     */
    public function get(Session $session): array;

    /**
     * Set the values.
     *
     * @param array   $values  The values to set.
     * @param Session $session The shop session.
     *
     * @return void
     */
    public function set(array $values, Session $session): void;

    /**
     * Set the values.
     *
     * @param mixed   $value   The value to add.
     * @param Session $session The shop session.
     *
     * @return void
     */
    public function push($value, Session $session): void;

    /**
     * Remove all values.
     *
     * @param Session $session The shop session.
     *
     * @return void
     */
    public function reset(Session $session): void;
}
