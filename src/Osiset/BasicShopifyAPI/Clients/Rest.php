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

    /**
     * Processes the "Link" header.
     *
     * @return stdClass
     */
    protected function extractLinkHeader(string $header): stdClass
    {
        $links = [
            'next'     => null,
            'previous' => null,
        ];
        $regex = '/<.*page_info=([a-z0-9\-_]+).*>; rel="?{type}"?/i';

        foreach (array_keys($links) as $type) {
            preg_match(str_replace('{type}', $type, $regex), $header, $matches);
            $links[$type] = isset($matches[1]) ? $matches[1] : null;
        }

        return (object) $links;
    }
}
