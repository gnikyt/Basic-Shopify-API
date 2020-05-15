<?php

namespace Osiset\BasicShopifyAPI\Test;

use Exception;
use GuzzleHttp\Client;
use Osiset\BasicShopifyAPI\Options;
use Osiset\BasicShopifyAPI\Session;
use Osiset\BasicShopifyAPI\Test\BaseTest;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;

class BasicShopifyAPITest extends BaseTest
{
    public function testSetClient(): void
    {
        // Create the client
        $api = $this->buildClient();

        // Swap the client
        $client = new Client();
        $api->setClient($client);

        $this->assertEquals($client, $api->getClient());
        $this->assertEquals($client, $api->getGraphClient()->getClient());
        $this->assertEquals($client, $api->getRestClient()->getClient());
    }

    public function testSetAndGetOptions(): void
    {
        // Create the client
        $api = $this->buildClient();

        // Make options
        $opts = new Options();
        $opts->setType(false);
        $api->setOptions($opts);

        $this->assertEquals($opts, $api->getOptions());
    }

    public function testSetAndGetSession(): void
    {
        // Create the client
        $api = $this->buildClient();

        // Make a session
        $session = new Session('example.myshopify.com', 'abc123');
        $api->setSession($session);

        $this->assertEquals($session, $api->getSession());
    }

    public function testWithSession(): void
    {
        // Create the client
        $api = $this->buildClient();

        // Make a session
        $session = new Session('example.myshopify.com', 'abc123');
        $api->setSession($session);

        // Make another session
        $session2 = new Session('example-two.myshopify.com', 'abc123');

        $self = $this;
        $api->withSession($session2, function () use ($self, $api): void {
            /** @var BasicShopifyAPI $this */
            /** @var TestCase $self */
            $self->assertNotEquals($api, $this);
            $self->assertNotEquals($api->getSession(), $this->getSession());
        });
    }

    public function testVerifyRequestFailWithNoParams(): void
    {
        // Create the client
        $api = $this->buildClient([], function (Options $opts): Options {
            return $opts->setApiSecret('hush');
        });

        $this->assertFalse($api->verifyRequest([]));
    }

    public function testVerifyRequestFailWithNoSecret(): void
    {
        $this->expectException(Exception::class);

        // Create the client
        $api = $this->buildClient();
        $api->verifyRequest([]);
    }

    public function testVerifyRequestPassing(): void
    {
        $hmac = '4712bf92ffc2917d15a2f5a273e39f0116667419aa4b6ac0b3baaf26fa3c4d20';
        $params = [
            'code'      => '0907a61c0c8d55e99db179b68161bc00',
            'hmac'      => $hmac,
            'shop'      => 'some-shop.myshopify.com',
            'timestamp' => '1337178173',
        ];

        // Create the client
        $api = $this->buildClient([], function (Options $opts): Options {
            return $opts->setApiSecret('hush');
        });

        $this->assertTrue($api->verifyRequest($params));
    }
}
