<?php

namespace Osiset\BasicShopifyAPI\Middleware;

use Psr\Http\Message\RequestInterface;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\Traits\IsRequestType;

/**
 * Update request times for calls.
 */
class UpdateRequestTime
{
    use IsRequestType;

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
                // Get the client
                $client = $self->isRestRequest($request->getUri()) ?
                    $self->api->getRestClient() :
                    $self->api->getGraphClient();

                $timestamps = $client->getTimeStore()->get();
                $currentTime = $client->getTimeDeferrer()->getCurrentTime();

                // Do checks for graph or rest here

                return $handler($request, $options);
            };
        };
    }
}
