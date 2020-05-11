<?php

namespace Osiset\BasicShopifyAPI\Middleware;

use Psr\Http\Message\RequestInterface;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Psr\Http\Message\ResponseInterface;

/**
 * Update API limits for REST and GraphQL calls.
 */
class UpdateApiLimits
{
    /**
     * Header used for REST limits.
     *
     * @var string
     */
    public const REST_LIMIT_HEADER = 'http_x_shopify_shop_api_call_limit';

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

    /**
     * Run.
     *
     * @return callable
     */
    public function __invoke(): callable
    {
        $self = $this;
        return function (callable $handler) use ($self) {
            return function (RequestInterface $request, array $options) use ($self, $handler) {
                $promise = $handler($request, $options);
                return $promise->then(
                    function (ResponseInterface $response) use ($self, $handler) {
                        if ($self->isRestRequest($response)) {
                            $self->updateRestLimits($response);
                        }

                        if ($self->isGraphRequest($response)) {
                            $self->updateGraphCosts($response);
                        }

                        return $response;
                    }
                );
            };
        };
    }

    /**
     * Check if this is a REST request by sniffing headers.
     *
     * @param ResponseInterface $response
     *
     * @return bool
     */
    protected function isRestRequest(ResponseInterface $response): bool
    {
        return $response->hasHeader(self::REST_LIMIT_HEADER);
    }

    /**
     * Check if this is a GraphQL request by sniffing headers.
     *
     * @param ResponseInterface $response
     * @return boolean
     */
    protected function isGraphRequest(ResponseInterface $response): bool
    {
        return !$this->isRestRequest($response);
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
        $body = $client->toResponse($response);

        if (!isset($body['extensions']) || !isset($body['extensions']['cost'])) {
            // Non-existant, exit
            return;
        }

        // Update the costs
        $cost = $body['extensions']['cost'];
        $client->setLimits([
            'left'          => (int)
                $cost['throttleStatus']['currentlyAvailable'],
            'made'          => (int)
                ($cost['throttleStatus']['maximumAvailable'] - $cost['throttleStatus']['currentlyAvailable']),
            'limit'         => (int)
                $cost['throttleStatus']['maximumAvailable'],
            'restoreRate'   => (int)
                $cost['throttleStatus']['restoreRate'],
            'requestedCost' => (int)
                $cost['requestedQueryCost'],
            'actualCost'    => (int)
                $cost['actualQueryCost'],
        ]);
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
        $header = $response->getHeader(self::REST_LIMIT_HEADER);
        if (!$header) {
            // Non-existant, exit
            return;
        }

        // Update the limits
        $calls = explode('/', $header[0]);
        $client = $this->api->getRestClient();
        $client->setLimits([
            'left'  => (int) $calls[1] - $calls[0],
            'made'  => (int) $calls[0],
            'limit' => (int) $calls[1],
        ]);
    }
}
