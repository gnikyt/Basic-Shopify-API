<?php

namespace Osiset\BasicShopifyAPI\Contracts;

use Osiset\BasicShopifyAPI\Contracts\TimeStorer;
use Osiset\BasicShopifyAPI\Contracts\TimeDeferrer;

/**
 * Reprecents time tracking.
 */
interface TimeTracker
{
    /**
     * Get the time store implementation.
     *
     * @return TimeStorer
     */
    public function getTimeStore(): TimeStorer;

    /**
     * Get the time deferrer implementation.
     *
     * @return TimeDeferrer
     */
    public function getTimeDeferrer(): TimeDeferrer;
}
