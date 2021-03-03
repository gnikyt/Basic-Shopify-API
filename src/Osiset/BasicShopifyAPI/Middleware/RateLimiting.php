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
        if (count($times) !== $api->getOptions()->getRestLimit()) {
            // Not at our limit yet, allow through without limiting
            return false;
        }

        // Determine if this call has passed the window
        $firstTime = end($times);
        $windowTime = $firstTime + 1;
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
        $lastTime = isset($lastTime[0]) ? $lastTime[0] : 0;

        // Get the last request cost and left points (leftPoints)
        /** @var int $lastCost */
        $lastCost = $ls->get($api->getSession());
        $leftPoints = isset($lastCost[0]) && isset($lastCost[0]['left']) ? $lastCost[0]['left'] : 0;
        $lastCost = isset($lastCost[0]) && isset($lastCost[0]['actualCost']) ? $lastCost[0]['actualCost'] : 0;

        if ($lastTime === 0 || $lastCost === 0) {
            // This is the first request, nothing to do
            return false;
        }

        // How many points can be spent every second, security factor and time difference
        $pointsEverySecond = $api->getOptions()->getGraphLimit();
        $securityFactor = $api->getOptions()->getGraphSecurityFactor();
        $timeDiff = $currentTime - $lastTime;

        // How many points we have spent over leak rate (time * pointsEverySecond)
        $overCost = $lastCost - ($timeDiff * $pointsEverySecond);

        if (($overCost > 0) && ($leftPoints < ($lastCost * $securityFactor))) {
            //lastCost is more than "estimated recovered points" AND leftPoints is less than lastCost * security factor
            $td->sleep($overCost/$pointsEverySecond * 1000000);
            //calls usleep($microseconds), with enought time to recover $overCost
            return true;
        }

        return false;
    }
}
