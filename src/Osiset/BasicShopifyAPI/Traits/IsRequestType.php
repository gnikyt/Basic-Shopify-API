<?php

namespace Osiset\BasicShopifyAPI\Traits;

/**
 * Determine GraphQL or REST request type.
 */
trait IsRequestType
{
    /**
     * Determines if the request is to Graph API.
     *
     * @param string $uri The request URI.
     *
     * @return bool
     */
    protected function isGraphRequest(string $uri): bool
    {
        return strpos($uri, 'graphql.json') !== false;
    }

    /**
     * Determines if the request is to REST API.
     *
     * @param string $uri The request URI.
     *
     * @return bool
     */
    protected function isRestRequest(string $uri): bool
    {
        return $this->isGraphRequest($uri) === false;
    }
}
