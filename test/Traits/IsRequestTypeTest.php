<?php

namespace Osiset\BasicShopifyAPI\Test\Middleware;

use Osiset\BasicShopifyAPI\Test\BaseTest;
use Osiset\BasicShopifyAPI\Traits\IsRequestType;
use PHPUnit\Framework\TestCase;

class IsRequestTypeTest extends BaseTest
{
    public function test(): void
    {
        // Create anon class
        $klass = new class {
            use IsRequestType;

            private $self;

            public function setSelf(TestCase $self): void
            {
                $this->self = $self;
            }

            public function testGraph(): void
            {
                $this->self->assertTrue($this->isGraphRequest('/admin/api/graphql.json'));
                $this->self->assertFalse($this->isGraphRequest('/admin/api/unstable/shop.json'));
            }

            public function testRest(): void
            {
                $this->self->assertFalse($this->isRestRequest('/admin/api/graphql.json'));
                $this->self->assertTrue($this->isRestRequest('/admin/api/unstable/shop.json'));
            }
        };

        $klass->setSelf($this);
        $klass->testGraph();
        $klass->testRest();
    }
}
