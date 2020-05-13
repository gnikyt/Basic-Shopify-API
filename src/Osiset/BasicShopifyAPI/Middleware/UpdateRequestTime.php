<?php

namespace Osiset\BasicShopifyAPI\Middleware;

use Psr\Http\Message\RequestInterface;
use Osiset\BasicShopifyAPI\Traits\IsRequestType;
use Osiset\BasicShopifyAPI\Middleware\AbstractMiddleware;

/**
 * Update request times for calls.
 */
class UpdateRequestTime extends AbstractMiddleware
{
    use IsRequestType;

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
            // Get the client
            $client = $self->isRestRequest($request->getUri()) ?
                $self->api->getRestClient() :
                $self->api->getGraphClient();

            $client->getTimeStore()->push(
                $client->getTimeDeferrer()->getCurrentTime()
            );

            return $handler($request, $options);
        };
    }
}
