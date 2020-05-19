<?php

namespace Osiset\BasicShopifyAPI\Middleware;

use Psr\Http\Message\RequestInterface;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\Traits\IsRequestType;
use Osiset\BasicShopifyAPI\Middleware\AbstractMiddleware;

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

        // Get current, last request time, and time difference
        $currentTime = $td->getCurrentTime();
        $lastTime = $ts->get($api->getSession());
        $lastTime = isset($lastTime[0]) ? $lastTime[0] : 0;

        if ($lastTime === 0) {
            // This is the first request, nothing to do
            return false;
        }
        
        // Calculate how many calls can be made every X microseconds
        $callEveryMs = 1000000 / $api->getOptions()->getRestLimit();
        $timeDiff = round($currentTime - $lastTime, 3);

        if ($timeDiff < $callEveryMs) {
            // Over the limit, sleep X microseconds until we can run again
            $td->sleep($callEveryMs - $timeDiff);
            return true;
        }

        return false;
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

        // Get the last request cost
        /** @var int $lastCost */
        $lastCost = $ls->get($api->getSession());
        $lastCost = isset($lastCost['actualCost']) ? $lastCost['actualCost'] : 0;

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
