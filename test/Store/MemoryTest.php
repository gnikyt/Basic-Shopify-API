<?php

namespace Osiset\BasicShopifyAPI\Test\Store;

use Osiset\BasicShopifyAPI\Session;
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
        $this->assertSame([], $mem->all());

        // Test set and get
        $mem->set(['b', 'a'], $session);
        $this->assertSame(
            ['b', 'a'],
            $mem->get($session)
        );

        // Test push
        $mem->push('c', $session);
        $this->assertSame(
            ['c', 'b', 'a'],
            $mem->get($session)
        );

        // Test reset
        $mem->reset($session);
        $this->assertSame([], $mem->get($session));
    }
}
