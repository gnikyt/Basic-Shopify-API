<?php

namespace Gnikyt\BasicShopifyAPI\Middleware;

use Gnikyt\BasicShopifyAPI\BasicShopifyAPI;

abstract class AbstractMiddleware
{
    /**
     * The API instance.
     *
     * @var BasicShopifyAPI
     */
    protected $api;

    /**
     * Setup.
     *
     * @param BasicShopifyAPI $api The API instance.
     *
     * @return self
     */
    public function __construct(BasicShopifyAPI $api)
    {
        $this->api = $api;
    }
}
