<?php

namespace Osiset\BasicShopifyAPI\Test;

use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\BasicShopifyAPI\Test\BaseTest;

class ResponseAccessTest extends BaseTest
{
    public function testArrayAndObjectAccess(): void
    {
        $resp = new ResponseAccess([
            'a' => 1,
            'b' => 2,
            'c' => ['d' => 3],
        ]);

        // Array set
        $resp['e'] = 6;

        // Object set
        $resp->f = 7;

        // Object access
        $this->assertTrue(isset($resp->c));
        $this->assertEquals(1, $resp->a);
        $this->assertInstanceOf(ResponseAccess::class, $resp->c);

        // Array access
        $this->assertTrue(!empty($resp['a']));
        $this->assertTrue(isset($resp['e']));

        // Array unset
        unset($resp['e']);
        $this->assertFalse(isset($resp['e']));
    }

    public function testErrorAccess(): void
    {
        // No error state
        $resp = new ResponseAccess(['a' => 1]);
        $this->assertFalse($resp->hasErrors());
        $this->assertNull($resp->getErrors());

        // Error state
        $resp = new ResponseAccess(['error' => 'Not found']);
        $this->assertTrue($resp->hasErrors());
        $this->assertEquals('Not found', $resp->getErrors());
    }

    public function testIteratorAndCount(): void
    {
        $names = [
            'John',
            'Tim',
            'Tommy',
        ];
        $resp = new ResponseAccess([
            'names' => $names,
        ]);

        $i = 0;
        foreach ($resp['names'] as $r) {
            $this->assertEquals($names[$i], $r);
            $i += 1;
        }

        $this->assertEquals(count($names), count($resp['names']));
    }

    public function testKeysAndValues(): void
    {
        $names = [
            'John',
            'Tim',
            'Tommy',
        ];
        $resp = new ResponseAccess([
            'names' => $names,
        ]);

        $this->assertEquals(['names'], $resp->keys());
        $this->assertEquals(array_values($names), $resp->names->values());
    }

    public function testJsonSerialize(): void
    {
        $names = [
            'John',
            'Tim',
            'Tommy',
        ];
        $resp = new ResponseAccess([
            'names' => $names,
        ]);

        $this->assertEquals(json_encode(['names' => $names]), json_encode($resp));
    }
}
