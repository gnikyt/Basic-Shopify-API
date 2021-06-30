<?php

namespace Osiset\BasicShopifyAPI\Test;

use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\BasicShopifyAPI\Session;

class SessionTest extends BaseTest
{
    public function testGettersAndSetters(): void
    {
        $session = new Session('example.myshopify.com', 'abc123', ['id' => 123]);

        $this->assertSame('example.myshopify.com', $session->getShop());
        $this->assertSame('abc123', $session->getAccessToken());
        $this->assertTrue($session->hasUser());
        $this->assertInstanceOf(ResponseAccess::class, $session->getUser());
    }
}
