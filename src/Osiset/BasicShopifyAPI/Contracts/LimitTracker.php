<?php

namespace Osiset\BasicShopifyAPI\Contracts;

/**
 * Reprecents common request limits tracking.
 */
interface LimitTracker
{
    /**
     * Update the cost limits.
     * Used by middleware.
     *
     * @param array $limits
     *
     * @return void
     */
    public function setLimits(array $limits): void;

    /**
     * Get the cost limits.
     *
     * @return array
     */
    public function getLimits(): array;
}
