<?php

namespace Osiset\BasicShopifyAPI\Contracts;

use GuzzleHttp\Promise\Promise;

/**
 * Reprecents Graph client.
 */
interface GraphRequester
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
