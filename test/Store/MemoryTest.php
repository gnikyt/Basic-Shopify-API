<?php

namespace Osiset\BasicShopifyAPI\Test;

use Osiset\BasicShopifyAPI\Session;
use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\BasicShopifyAPI\Store\Memory;
use Osiset\BasicShopifyAPI\Test\BaseTest;

class MemoryTest extends BaseTest
{
    public function test(): void
    {
        // Setup the store
        $mem = new Memory();
        $session = new Session('example.myshopify.com');

        // Test all
        $this->assertEquals([], $mem->all());

        // Test set and get
        $mem->set(['b', 'a'], $session);
        $this->assertEquals(
            ['b', 'a'],
            $mem->get($session)
        );

        // Test push
        $mem->push('c', $session);
        $this->assertEquals(
            ['c', 'b', 'a'],
            $mem->get($session)
        );

        // Test reset
        $mem->reset($session);
        $this->assertEquals([], $mem->get($session));
    }
}
