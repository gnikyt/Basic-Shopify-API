<?php

namespace Osiset\BasicShopifyAPI\Middleware;

use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\Traits\IsRequestType;
use Psr\Http\Message\RequestInterface;

/**
 * Handle basic request rate limiting for REST and GraphQL.
 */
class RateLimiting extends AbstractMiddleware
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
            if ($self->isRestRequest($request->getUri())) {
                $this->handleRest($self->api);
            } else {
                $this->handleGraph($self->api);
            }

            return $handler($request, $options);
        };
    }

    /**
     * Handle REST checks.
     *
     * @param BasicShopifyAPI $api
     *
     * @return bool
     */
    protected function handleRest(BasicShopifyAPI $api): bool
    {
        // Get the client
        $client = $api->getRestClient();
        $td = $client->getTimeDeferrer();
        $ts = $client->getTimeStore();

        $times = $ts->get($api->getSession());
        if (count($times) < $api->getOptions()->getRestLimit()) {
            // Not at our limit yet, allow through without limiting
            return false;
        }

        // Determine if this call has passed the window
        $windowTime = end($times) + 1000000;
        $currentTime = $td->getCurrentTime();

        if ($currentTime > $windowTime) {
            // Call is passed the window, reset and allow through without limiting
            $ts->reset($api->getSession());

            return false;
        }

        // Call is inside the window and not at the call limit, sleep until window can be reset
        $sleepTime = $windowTime - $currentTime;
        $td->sleep($sleepTime < 0 ? 0 : $sleepTime);
        $ts->reset($api->getSession());

        return true;
    }

    /**
     * Handle GraphQL checks.
     *
     * @param BasicShopifyAPI $api
     *
     * @return bool
     */
    protected function handleGraph(BasicShopifyAPI $api): bool
    {
        // Get the client
        $client = $api->getGraphClient();
        $td = $client->getTimeDeferrer();
        $ts = $client->getTimeStore();
        $ls = $client->getLimitStore();

        // Get current, last request time, and time difference
        $currentTime = $td->getCurrentTime();
        $lastTime = $ts->get($api->getSession());
        $lastTime = $lastTime[0] ?? 0;

        // Get the last request cost
        $lastCost = $ls->get($api->getSession());
        /** @var int $lastCost */
        $lastCost = $lastCost[0]['actualCost'] ?? 0;
        if ($lastTime === 0 || $lastCost === 0) {
            // This is the first request, nothing to do
            return false;
        }

        // How many points can be spent every second and time difference
        $pointsEverySecond = $api->getOptions()->getGraphLimit();
        $timeDiff = $currentTime - $lastTime;
        if ($timeDiff < 1000000 && $lastCost > $pointsEverySecond) {
            // Less than a second has passed and the cost is over the limit
            $td->sleep(1000000 - $timeDiff);

            return true;
        }

        return false;
    }
}
