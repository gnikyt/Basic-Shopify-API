<?php

namespace Osiset\BasicShopifyAPI\Test\Middleware;

use ReflectionMethod;
use GuzzleHttp\Psr7\Request;
use Osiset\BasicShopifyAPI\Session;
use Psr\Http\Message\RequestInterface;
use Osiset\BasicShopifyAPI\Test\BaseTest;
use Osiset\BasicShopifyAPI\Middleware\RateLimiting;

class RateLimitingTest extends BaseTest
{
    public function testRestSleep(): void
    {
        // Make the method accessible
        $method = new ReflectionMethod(RateLimiting::class, 'handleRest');
        $method->setAccessible(true);

        // Create the client
        $api = $this->buildClient([]);
        $api->setSession(new Session('example.myshopify.com'));

        // Create fake times
        $td = $api->getRestClient()->getTimeDeferrer();
        $currentTime = $td->getCurrentTime();
        $firstTime = $currentTime - 0.200;
        $lastTime = $currentTime - 0.100;

        // Fill fake times
        $ts = $api->getRestClient()->getTimeStore();
        $ts->set([$lastTime, $firstTime], $api->getSession());

        // Given we have 2 previous calls within 1 second window, sleep should trigger
        $result = $method->invoke(new RateLimiting($api), $api);
        $this->assertTrue($result);
    }

    public function testRestNoSleep(): void
    {
        // Make the method accessible
        $method = new ReflectionMethod(RateLimiting::class, 'handleRest');
        $method->setAccessible(true);

        // Create the client
        $api = $this->buildClient([]);
        $api->setSession(new Session('example.myshopify.com'));

        // Create fake times
        $td = $api->getRestClient()->getTimeDeferrer();
        $currentTime = $td->getCurrentTime();
        $firstTime = $currentTime - 0.200;
        $lastTime = $currentTime - 0.100;

        // Fill fake times
        $ts = $api->getRestClient()->getTimeStore();
        $ts->set([$lastTime, $firstTime], $api->getSession());

        // Even though two requests happened within 1 second window,
        // If we do the "next" call after the window time, it should
        // not sleep and reset the times
        sleep(1);
        $result = $method->invoke(new RateLimiting($api), $api);
        $this->assertFalse($result);
    }

    public function testGraphSleep(): void
    {
        // Make the method accessible
        $method = new ReflectionMethod(RateLimiting::class, 'handleGraph');
        $method->setAccessible(true);

        // Create the client
        $api = $this->buildClient([]);
        $api->setSession(new Session('example.myshopify.com'));

        // Create fake times
        $td = $api->getGraphClient()->getTimeDeferrer();
        $currentTime = $td->getCurrentTime();
        $lastTime = $currentTime - 900000; // -900ms

        // Fill fake times
        $ts = $api->getGraphClient()->getTimeStore();
        $ts->set([$lastTime], $api->getSession());

        // Fill in fake costs
        $ls = $api->getGraphClient()->getLimitStore();
        $ls->set(['actualCost' => 400], $api->getSession());

        // Given last cost was over the default 50 points and last request was less than a second ago, we should sleep
        $result = $method->invoke(new RateLimiting($api), $api);
        $this->assertTrue($result);
    }

    public function testGraphNoSleep(): void
    {
        // Make the method accessible
        $method = new ReflectionMethod(RateLimiting::class, 'handleGraph');
        $method->setAccessible(true);

        // Create the client
        $api = $this->buildClient([]);
        $api->setSession(new Session('example.myshopify.com'));

        // Create fake times
        $td = $api->getGraphClient()->getTimeDeferrer();
        $currentTime = $td->getCurrentTime();
        $lastTime = $currentTime - 1000000; // -1secs

        // Fill fake times
        $ts = $api->getGraphClient()->getTimeStore();
        $ts->set([$lastTime], $api->getSession());

        // Fill in fake costs
        $ls = $api->getGraphClient()->getLimitStore();
        $ls->set(['actualCost' => 50], $api->getSession());

        // Given last cost was 50 points within 1 second, we should not sleep
        $result = $method->invoke(new RateLimiting($api), $api);
        $this->assertFalse($result);
    }
}
