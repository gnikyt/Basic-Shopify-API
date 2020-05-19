<?php

namespace Osiset\BasicShopifyAPI\Contracts;

use Osiset\BasicShopifyAPI\Contracts\StateStorage;

/**
 * Reprecents common request limits tracking.
 */
interface LimitAccesser
{
    /**
     * Get the limit store implementation.
     *
     * @return StateStorage
     */
    public function getLimitStore(): StateStorage;
}
