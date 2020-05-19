<?php

namespace Osiset\BasicShopifyAPI\Contracts;

use Osiset\BasicShopifyAPI\Contracts\StateStorage;
use Osiset\BasicShopifyAPI\Contracts\TimeDeferrer;

/**
 * Reprecents time tracking.
 */
interface TimeAccesser
{
    /**
     * Get the time store implementation.
     *
     * @return StateStorage
     */
    public function getTimeStore(): StateStorage;

    /**
     * Get the time deferrer implementation.
     *
     * @return TimeDeferrer
     */
    public function getTimeDeferrer(): TimeDeferrer;
}
