<?php

namespace Osiset\BasicShopifyAPI\Middleware;

use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\Traits\IsResponseType;
use Osiset\BasicShopifyAPI\Traits\ResponseTransform;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Update API limits for REST and GraphQL calls.
 */
class UpdateApiLimits extends AbstractMiddleware
{
    use IsResponseType;
    use ResponseTransform;

    /**
     * Run.
     *
     * @param callable $handler
     *
     * @return callable
     */
    public function __invoke(callable $handler): callable
    {
        $self = $this;

        return function (RequestInterface $request, array $options) use ($self, $handler) {
            $promise = $handler($request, $options);

            return $promise->then(
                function (ResponseInterface $response) use ($self) {
                    if ($self->isRestResponse($response)) {
                        $self->updateRestLimits($response);
                    } else {
                        $self->updateGraphCosts($response);
                    }

                    return $response;
                }
            );
        };
    }

    /**
     * Update the GraphQL costs.
     *
     * @param ResponseInterface $response
     *
     * @return void
     */
    protected function updateGraphCosts(ResponseInterface $response): void
    {
        // Get the GraphQL client
        $client = $this->api->getGraphClient();
        $body = $this->toResponse($response->getBody());

        if (!isset($body['extensions']) || !isset($body['extensions']['cost'])) {
            // Non-existant, exit
            return;
        }

        // Update the costs
        $cost = $body['extensions']['cost'];
        $client->getLimitStore()->push(
            [
                'left' => (int)
                    $cost['throttleStatus']['currentlyAvailable'],
                'made' => (int)
                    ($cost['throttleStatus']['maximumAvailable'] - $cost['throttleStatus']['currentlyAvailable']),
                'limit' => (int)
                    $cost['throttleStatus']['maximumAvailable'],
                'restoreRate' => (int)
                    $cost['throttleStatus']['restoreRate'],
                'requestedCost' => (int)
                    $cost['requestedQueryCost'],
                'actualCost' => (int)
                    $cost['actualQueryCost'],
            ],
            $this->api->getSession()
        );
    }

    /**
     * Updates the REST API call limits from Shopify headers.
     *
     * @param ResponseInterface $response
     *
     * @return void
     */
    protected function updateRestLimits(ResponseInterface $response): void
    {
        // Grab the API call limit header returned from Shopify
        $header = $response->getHeader(BasicShopifyAPI::HEADER_REST_API_LIMITS);
        if (!$header) {
            // Non-existant, exit
            return;
        }

        // Update the limits
        $calls = explode('/', $header[0]);
        $client = $this->api->getRestClient();
        $client->getLimitStore()->push(
            [
                'left' => (int) $calls[1] - (int) $calls[0],
                'made' => (int) $calls[0],
                'limit' => (int) $calls[1],
            ],
            $this->api->getSession()
        );
    }
}
