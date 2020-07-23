<?php

namespace Osiset\BasicShopifyAPI\Test;

use Osiset\BasicShopifyAPI\Session;
use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\BasicShopifyAPI\Test\BaseTest;

class SessionTest extends BaseTest
{
    public function testGettersAndSetters(): void
    {
        $session = new Session('example.myshopify.com', 'abc123', ['id' => 123]);

        $this->assertEquals('example.myshopify.com', $session->getShop());
        $this->assertEquals('abc123', $session->getAccessToken());
        $this->assertTrue($session->hasUser());
        $this->assertInstanceOf(ResponseAccess::class, $session->getUser());
    }
}
