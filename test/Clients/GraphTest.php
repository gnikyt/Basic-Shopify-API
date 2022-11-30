<?php

namespace Gnikyt\BasicShopifyAPI\Test\Clients;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Gnikyt\BasicShopifyAPI\Options;
use Gnikyt\BasicShopifyAPI\ResponseAccess;
use Gnikyt\BasicShopifyAPI\Session;
use Gnikyt\BasicShopifyAPI\Test\BaseTest;

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
        /** @var \GuzzleHttp\Handler\MockHandler $handler */
        $handler = $api->getOptions()->getGuzzleHandler();
        $tokenHeader = $handler->getLastRequest()->getHeader('X-Shopify-Access-Token')[0];

        $this->assertIsArray($response);
        $this->assertInstanceOf(GuzzleResponse::class, $response['response']);
        $this->assertSame(200, $response['response']->getStatusCode());
        $this->assertInstanceOf(ResponseAccess::class, $response['body']);
        $this->assertFalse($response['errors']);
        $this->assertSame('!#@', $tokenHeader);
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

        $this->assertIsArray($response);
        $this->assertInstanceOf(GuzzleResponse::class, $response['response']);
        $this->assertInstanceOf(RequestException::class, $response['exception']);
        $this->assertSame(404, $response['response']->getStatusCode());
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
