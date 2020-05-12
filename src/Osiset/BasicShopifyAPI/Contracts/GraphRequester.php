<?php

namespace Osiset\BasicShopifyAPI\Contracts;

use GuzzleHttp\Promise\Promise;
use Osiset\BasicShopifyAPI\Contracts\TimeTracker;
use Osiset\BasicShopifyAPI\Contracts\LimitTracker;

/**
 * Reprecents Graph client.
 */
interface GraphRequester extends LimitTracker, TimeTracker
{
    /**
     * Runs a request to the Shopify API.
     *
     * @param string $query     The GraphQL query.
     * @param array  $variables The optional variables for the query.
     * @param bool   $sync      Optionally wait for the request to finish.
     *
     * @return array|Promise
     */
    public function request(string $query, array $variables = [], bool $sync = true);
}
