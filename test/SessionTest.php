<?php

namespace Osiset\BasicShopifyAPI\Test;

use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\BasicShopifyAPI\Session;

class SessionTest extends BaseTest
{
    public function testGettersAndSettersWithUser(): void
    {
        $session = new Session(
            'example.myshopify.com',
            'abc123',
            new ResponseAccess([
                'associated_user' => ['first_name' => 'Tom'],
                'account_number' => 123,
            ])
        );

        $this->assertSame('example.myshopify.com', $session->getShop());
        $this->assertSame('abc123', $session->getAccessToken());
        $this->assertTrue($session->hasUser());
        $this->assertInstanceOf(ResponseAccess::class, $session->getUser());
    }

    public function testGettersAndSettersWithoutUser(): void
    {
        $session = new Session('example.myshopify.com', 'abc123');

        $this->assertSame('example.myshopify.com', $session->getShop());
        $this->assertSame('abc123', $session->getAccessToken());
        $this->assertFalse($session->hasUser());
        $this->assertNull($session->getUser());
    }
}
