<?php

namespace Gnikyt\BasicShopifyAPI\Test;

use Gnikyt\BasicShopifyAPI\ResponseAccess;

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
        $this->assertSame(1, $resp->a);
        $this->assertInstanceOf(ResponseAccess::class, $resp->c);

        // Array access
        $this->assertNotEmpty($resp['a']);
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
        $this->assertSame('Not found', $resp->getErrors());
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
            $this->assertSame($names[$i], $r);
            $i += 1;
        }

        $this->assertCount(count($names), $resp['names']);
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

        $this->assertSame(['names'], $resp->keys());
        $this->assertSame(array_values($names), $resp->names->values());
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

        $this->assertSame(json_encode(['names' => $names]), json_encode($resp));
    }

    public function testToArray(): void
    {
        $names = [
            'names' => [
                'John',
                'Tim',
                'Tommy',
            ],
        ];
        $resp = new ResponseAccess($names);

        $this->assertSame($names, $resp->toArray());
    }
}
