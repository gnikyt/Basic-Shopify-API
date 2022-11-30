<?php

namespace Gnikyt\BasicShopifyAPI\Test\Traits;

use Gnikyt\BasicShopifyAPI\ResponseAccess;
use Gnikyt\BasicShopifyAPI\Test\BaseTest;
use Gnikyt\BasicShopifyAPI\Traits\ResponseTransform;
use GuzzleHttp\Psr7\Response;

class ResponseTransformTest extends BaseTest
{
    public function test(): void
    {
        // Create a response to use for body stream
        $response = new Response(200, [], file_get_contents(__DIR__.'/../fixtures/rest/admin__shop.json'));

        // Create a anon class
        $kclass = new class () {
            use ResponseTransform;
        };
        $result = $kclass->toResponse($response->getBody());

        $this->assertInstanceOf(ResponseAccess::class, $result);
    }
}
