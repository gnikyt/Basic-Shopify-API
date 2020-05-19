<?php

namespace Osiset\BasicShopifyAPI\Test\Middleware;

use GuzzleHttp\Psr7\Request;
use Osiset\BasicShopifyAPI\Session;
use Psr\Http\Message\RequestInterface;
use Osiset\BasicShopifyAPI\Test\BaseTest;
use Osiset\BasicShopifyAPI\Middleware\UpdateRequestTime;

class UpdateRequestTimeTest extends BaseTest
{
    public function testRuns(): void
    {
        // Create the client
        $api = $this->buildClient([]);
        $api->setSession(new Session('example.myshopify.com'));

        // Create the middleware instance
        $mw = new UpdateRequestTime($api);

        // Ensure its empty
        $this->assertEquals(
            [],
            $api->getRestClient()->getTimeStore()->get($api->getSession())
        );

        // Run a request
        $mw(
            function (RequestInterface $request, array $options): void {
                return;
            }
        )(new Request('GET', '/admin/shop.json'), []);

        // Check we have timestamp now
        $this->assertNotEmpty(
            $api->getRestClient()->getTimeStore()->get($api->getSession())
        );
    }
}
