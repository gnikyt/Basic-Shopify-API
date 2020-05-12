<?php

namespace Osiset\BasicShopifyAPI\Traits;

use Psr\Http\Message\ResponseInterface;

/**
 * Determine GraphQL or REST response type.
 */
trait IsResponseType
{
    /**
     * Check if this is a REST request by sniffing headers.
     *
     * @param ResponseInterface $response
     *
     * @return bool
     */
    protected function isRestResponse(ResponseInterface $response): bool
    {
        return $response->hasHeader('http_x_shopify_shop_api_call_limit');
    }

    /**
     * Check if this is a GraphQL request by sniffing headers.
     *
     * @param ResponseInterface $response
     * @return boolean
     */
    protected function isGraphResponse(ResponseInterface $response): bool
    {
        return !$this->isRestResponse($response);
    }
}
