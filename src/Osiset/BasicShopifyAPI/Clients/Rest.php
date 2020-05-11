<?php

namespace Osiset\BasicShopifyAPI;

use Osiset\BasicShopifyAPI\Clients\AbstractClient;
use Osiset\BasicShopifyAPI\Contracts\RestRequester;

/**
 * REST handler.
 */
class Rest extends AbstractClient implements RestRequester
{
    /**
     * The current API call limits from last request.
     *
     * @var array
     */
    protected $apiCallLimits = [
        'left'  => 0,
        'made'  => 0,
        'limit' => 40,
    ];
}
