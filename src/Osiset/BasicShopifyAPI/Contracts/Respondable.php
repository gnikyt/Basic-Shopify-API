<?php

namespace Osiset\BasicShopifyAPI\Contracts;

use Osiset\BasicShopifyAPI\Response;
use Psr\Http\Message\StreamInterface;

/**
 * Reprecents ability to respond to data tranformation.
 */
interface Respondable
{
    /**
     * Convert request response to response object.
     *
     * @return Response
     */
    public function toResponse(StreamInterface $body): Response;
}
