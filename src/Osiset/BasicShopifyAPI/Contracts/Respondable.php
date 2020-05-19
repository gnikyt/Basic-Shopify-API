<?php

namespace Osiset\BasicShopifyAPI\Contracts;

use Osiset\BasicShopifyAPI\ResponseAccess;
use Psr\Http\Message\StreamInterface;

/**
 * Reprecents ability to respond to data tranformation.
 */
interface Respondable
{
    /**
     * Convert request response to response object.
     *
     * @return ResponseAccess
     */
    public function toResponse(StreamInterface $body): ResponseAccess;
}
