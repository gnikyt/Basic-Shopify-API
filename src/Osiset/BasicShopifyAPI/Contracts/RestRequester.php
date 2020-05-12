<?php

namespace Osiset\BasicShopifyAPI\Contracts;

use Osiset\BasicShopifyAPI\Contracts\TimeTracker;
use Osiset\BasicShopifyAPI\Contracts\LimitTracker;

/**
 * Reprecents REST client.
 */
interface RestRequester extends LimitTracker, TimeTracker
{
    // ...
}
