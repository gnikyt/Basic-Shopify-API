<?php

namespace Osiset\BasicShopifyAPI\Clients;

use Osiset\BasicShopifyAPI\Response;
use Psr\Http\Message\StreamInterface;

/**
 * Base client class.
 */
abstract class AbstractClient
{
    /**
     * Convert request response to response object.
     *
     * @return Response
     */
    protected function toResponse(StreamInterface $body): Response
    {
        $decoded = json_decode($body, true, 512, JSON_BIGINT_AS_STRING);
        return new Response($decoded);
    }
}
