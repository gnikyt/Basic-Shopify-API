<?php

namespace Osiset\BasicShopifyAPI\Test\Clients;

use Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Psr7\Uri;
use Osiset\BasicShopifyAPI\Options;
use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\BasicShopifyAPI\Session;
use Osiset\BasicShopifyAPI\Test\BaseTest;

class RestTest extends BaseTest
{
    public function testGetBaseUri(): void
    {
        // Create the client
        $api = $this->buildClient([], function (Options $opts): Options {
            return $opts
                ->setApiKey('123')
                ->setApiPassword('abc');
        });
        $api->setSession(new Session('example.myshopify.com', 'abc123'));

        $this->assertInstanceOf(Uri::class, $api->getRestClient()->getBaseUri());
    }

    public function testGetBaseUriFailure(): void
    {
        $this->expectException(Exception::class);

        // Create the client but with missing values
        $api = $this->buildClient();
        $api->getRestClient()->getBaseUri();
    }

    public function testExtractLinkHeader(): void
    {
        // Setup the response
        $pageInfo = 'eyJsYXN0X2lkIjo0MDkwMTQ0ODQ5OTgyLCJsYXN_0X3ZhbHVlIjoiPGh0bWw-PGh0bWw-MiBZZWFyIERWRCwgQmx1LVJheSwgU2F0ZWxsaXRlLCBhbmQgQ2FibGUgRnVsbCBDaXJjbGXihKIgMTAwJSBWYWx1ZSBCYWNrIFByb2R1Y3QgUHJvdGVjdGlvbiB8IDIgYW4gc3VyIGxlcyBsZWN0ZXVycyBEVkQgZXQgQmx1LXJheSBldCBwYXNzZXJlbGxlcyBtdWx0aW3DqWRpYXMgYXZlYyByZW1pc2Ugw6AgMTAwICUgQ2VyY2xlIENvbXBsZXQ8c3VwPk1DPFwvc3VwPjxcL2h0bWw-PFwvaHRtbD4iLCJkaXJlY3Rpb24iOiJuZXh0In0';
        $responses = [
            new GuzzleResponse(
                200,
                [
                    'http_x_shopify_shop_api_call_limit' => '1/80',
                    'link' => '<https://example.myshopify.com/admin/api/unstable/products.json?page_info='.$pageInfo.'>; rel="next"',
                ],
                file_get_contents(__DIR__.'/../fixtures/rest/admin__shop.json')
            ),
        ];

        // Create the client
        $api = $this->buildClient($responses, function (Options $opts): Options {
            return $opts
                ->setApiKey('123')
                ->setApiPassword('abc');
        });
        $api->setSession(new Session('example.myshopify.com', 'abc123'));

        // Run the request
        $result = $api->getRestClient()->request('GET', '/admin/shop.json');

        $this->assertSame($pageInfo, $result['link']['next']);
    }

    public function testRequestAccess(): void
    {
        // Setup the responses
        $responses = [
            new GuzzleResponse(
                200,
                [],
                file_get_contents(__DIR__.'/../fixtures/admin__oauth__access_token.json')
            ),
        ];

        // Create the client
        $api = $this->buildClient($responses, function (Options $opts): Options {
            return $opts
                ->setApiKey('123')
                ->setApiSecret('hush')
                ->setApiPassword('abc');
        });
        $api->setSession(new Session('example.myshopify.com'));

        // Request access
        $code = '!@#';
        $result = $api->requestAccessToken($code);
        $data = json_decode($api->getOptions()->getGuzzleHandler()->getLastRequest()->getBody(), true);

        $this->assertSame('f85632530bf277ec9ac6f649fc327f17', $result);
    }

    public function testRequestAccessFailure(): void
    {
        $this->expectException(Exception::class);

        // Create the client but with missing values
        $api = $this->buildClient();
        $api->requestAccess('!@#');
    }

    public function testRequestAndSetAccess(): void
    {
        // Setup the responses
        $responses = [
            new GuzzleResponse(
                200,
                [],
                file_get_contents(__DIR__.'/../fixtures/admin__oauth__access_token__grant.json')
            ),
        ];

        // Create the client
        $api = $this->buildClient($responses, function (Options $opts): Options {
            return $opts
                ->setApiKey('123')
                ->setApiSecret('hush')
                ->setApiPassword('abc');
        });

        // Make a session
        $session = new Session('example.myshopify.com');
        $api->setSession($session);

        // Request and set access
        $result = $api->requestAndSetAccess('!@#');

        $this->assertNotEquals($session, $api->getSession());
    }

    public function testGetAuthUrlForOffline(): void
    {
        // Create the client
        $api = $this->buildClient([], function (Options $options): Options {
            return $options->setApiKey('123');
        });
        $api->setSession(new Session('example.myshopify.com'));

        $this->assertSame(
            'https://example.myshopify.com/admin/oauth/authorize?client_id=123&scope=read_products%2Cwrite_products&redirect_uri=https%3A%2F%2Flocalapp.local%2F',
            $api->getAuthUrl(['read_products', 'write_products'], 'https://localapp.local/')
        );
    }

    public function testGetAuthUrlForPerUser(): void
    {
        // Create the client
        $api = $this->buildClient([], function (Options $options): Options {
            return $options->setApiKey('123');
        });
        $api->setSession(new Session('example.myshopify.com'));

        $this->assertSame(
            'https://example.myshopify.com/admin/oauth/authorize?client_id=123&scope=read_products%2Cwrite_products&redirect_uri=https%3A%2F%2Flocalapp.local%2F&grant_options%5B%5D=per-user',
            $api->getAuthUrl(['read_products', 'write_products'], 'https://localapp.local/', 'per-user')
        );
    }

    public function testGetAuthUrlFailure(): void
    {
        $this->expectException(Exception::class);

        // Create the client but with missing values
        $api = $this->buildClient();
        $api->getAuthUrl(['read_products', 'write_products'], 'https://localapp.local/');
    }

    public function testRequestSuccess(): void
    {
        // Setup the responses
        $responses = [
            new GuzzleResponse(
                200,
                ['http_x_shopify_shop_api_call_limit' => '2/80'],
                file_get_contents(__DIR__.'/../fixtures/rest/admin__shop.json')
            ),
        ];

        // Create the client
        $api = $this->buildClient($responses, function (Options $options): Options {
            return $options->setApiKey('123');
        });
        $api->setSession(new Session('example.myshopify.com', '!#@'));

        // Fake param just to test it receives it
        $response = $api->request('GET', '/admin/shop.json', ['limit' => 1, 'page' => 1], ['X-Special' => true]);
        $query = $api->getOptions()->getGuzzleHandler()->getLastRequest()->getUri()->getQuery();
        $tokenHeader = $api->getOptions()->getGuzzleHandler()->getLastRequest()->getHeader('X-Shopify-Access-Token')[0];
        $specialHeader = $api->getOptions()->getGuzzleHandler()->getLastRequest()->getHeader('X-Special')[0];

        $this->assertIsArray($response);
        $this->assertInstanceOf(GuzzleResponse::class, $response['response']);
        $this->assertSame(200, $response['response']->getStatusCode());
        $this->assertInstanceOf(ResponseAccess::class, $response['body']);
        $this->assertSame('limit=1&page=1', $query);
        $this->assertSame('!#@', $tokenHeader);
        $this->assertSame('1', $specialHeader);
    }

    public function testRequestForceRequestType(): void
    {
        // Setup the responses
        $responses = [
            new GuzzleResponse(
                200,
                [],
                file_get_contents(__DIR__.'/../fixtures/rest/admin__shop.json')
            ),
        ];

        // Create the client
        $api = $this->buildClient($responses, function (Options $options): Options {
            return $options->setApiKey('123');
        });
        $api->setSession(new Session('example.myshopify.com', '!#@'));

        // Fake param just to test it receives it
        $api->request('PUT', '/admin/shop.json', ['query' => ['limit' => 1]]);
        $query = $api->getOptions()->getGuzzleHandler()->getLastRequest()->getUri()->getQuery();

        $this->assertSame('limit=1', $query);
    }

    public function testRequestFailure(): void
    {
        // Setup the responses
        $responses = [
            new GuzzleResponse(
                404,
                ['http_x_shopify_shop_api_call_limit' => '2/80'],
                file_get_contents(__DIR__.'/../fixtures/rest/admin__shop_oops.json')
            ),
        ];

        // Create the client
        $api = $this->buildClient($responses, function (Options $options): Options {
            return $options->setApiKey('123');
        });
        $api->setSession(new Session('example.myshopify.com', '!#@'));

        // Fake param just to test it receives it
        $response = $api->rest('GET', '/admin/shop.json');

        $this->assertIsArray($response);
        $this->assertInstanceOf(GuzzleResponse::class, $response['response']);
        $this->assertInstanceOf(RequestException::class, $response['exception']);
        $this->assertSame(404, $response['response']->getStatusCode());
        $this->assertSame('Not Found', $response['body']);
    }

    public function testRequestFailureWithNoBody(): void
    {
        // Setup the responses
        $responses = [
            new GuzzleResponse(
                404,
                ['http_x_shopify_shop_api_call_limit' => '2/80']
            ),
        ];

        // Create the client
        $api = $this->buildClient($responses, function (Options $options): Options {
            return $options->setApiKey('123');
        });
        $api->setSession(new Session('example.myshopify.com', '!#@'));

        // Fake param just to test it receives it
        $response = $api->rest('GET', '/admin/shop.json');

        $this->assertSame(404, $response['response']->getStatusCode());
        $this->assertNull($response['body']);
    }

    public function testRequestAsync(): void
    {
        // Setup the responses
        $responses = [
            new GuzzleResponse(
                200,
                ['http_x_shopify_shop_api_call_limit' => '2/80'],
                file_get_contents(__DIR__.'/../fixtures/rest/admin__shop.json')
            ),
        ];

        // Create the client
        $api = $this->buildClient($responses, function (Options $options): Options {
            return $options->setApiKey('123');
        });
        $api->setSession(new Session('example.myshopify.com', '!#@'));

        // Run async
        $promise = $api->restAsync('GET', '/admin/shop.json');
        $promise->then(function (): void {
            $this->assertTrue(true);
        });
        $promise->wait();
    }
}
