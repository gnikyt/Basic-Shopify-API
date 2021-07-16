<?php

namespace Osiset\BasicShopifyAPI\Contracts;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Uri;

/**
 * Reprecents Graph client.
 */
interface GraphRequester extends LimitAccesser, TimeAccesser, SessionAware, ClientAware
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
     * Returns the base URI to use.
     *
     * @throws \Exception For missing shop domain.
     *
     * @return Uri
     */
    public function getBaseUri(): Uri;
}
