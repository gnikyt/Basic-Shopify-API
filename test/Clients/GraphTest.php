<?php

namespace Osiset\BasicShopifyAPI\Test\Clients;

use Osiset\BasicShopifyAPI\Options;
use Osiset\BasicShopifyAPI\Session;
use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\BasicShopifyAPI\Test\BaseTest;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

class GraphTest extends BaseTest
{
    protected $variables;
    protected $query;
    protected $mutation;

    public function setUp(): void
    {
        parent::setUp();

        // Query call
        $this->query = [
            '{ shop { products(first: 1) { edges { node { handle id } } } } }',
        ];

        // Variables
        $this->variables = [
            ['x' => 'y'],
        ];

        // Mutation call with variables
        $this->mutation = [
            'mutation collectionCreate($input: CollectionInput!) { collectionCreate(input: $input) { userErrors { field message } collection { id } } }',
            ['input' => ['title' => 'Test Collection']],
        ];
    }

    public function testRequestSuccess(): void
    {
        // Setup the responses
        $responses = [
            new GuzzleResponse(
                200,
                [],
                file_get_contents(__DIR__.'/../fixtures/graphql/shop_products.json')
            ),
        ];

        // Create the client
        $api = $this->buildClient($responses, function (Options $options): Options {
            return $options->setApiKey('123');
        });
        $api->setSession(new Session('example.myshopify.com', '!#@'));

        // Fake param just to test it receives it
        $response = $api->graph($this->query[0], $this->variables[0]);
        $tokenHeader = $api->getOptions()->getGuzzleHandler()->getLastRequest()->getHeader('X-Shopify-Access-Token')[0];

        $this->assertTrue(is_array($response));
        $this->assertInstanceOf(GuzzleResponse::class, $response['response']);
        $this->assertEquals(200, $response['response']->getStatusCode());
        $this->assertInstanceOf(ResponseAccess::class, $response['body']);
        $this->assertFalse($response['errors']);
        $this->assertEquals('!#@', $tokenHeader);
    }

    public function testRequestFailure(): void
    {
        // Setup the responses
        $responses = [
            new GuzzleResponse(404, [], '{}'),
        ];

        // Create the client
        $api = $this->buildClient($responses, function (Options $options): Options {
            return $options->setApiKey('123');
        });
        $api->setSession(new Session('example.myshopify.com', '!#@'));

        // Fake param just to test it receives it
        $response = $api->graph($this->query[0], $this->variables[0]);

        $this->assertTrue(is_array($response));
        $this->assertInstanceOf(GuzzleResponse::class, $response['response']);
        $this->assertInstanceOf(RequestException::class, $response['exception']);
        $this->assertEquals(404, $response['response']->getStatusCode());
        $this->assertNull($response['body']);
    }

    public function testRequestAsync(): void
    {
        // Setup the responses
        $responses = [
            new GuzzleResponse(
                200,
                [],
                file_get_contents(__DIR__.'/../fixtures/graphql/shop_products.json')
            ),
        ];

        // Create the client
        $api = $this->buildClient($responses, function (Options $options): Options {
            return $options->setApiKey('123');
        });
        $api->setSession(new Session('example.myshopify.com', '!#@'));

        // Run async
        $promise = $api->graphAsync($this->query[0]);
        $promise->then(function (): void {
            $this->assertTrue(true);
        });
        $promise->wait();
    }
}
