<?php

namespace Gnikyt\BasicShopifyAPI\Test\Traits;

use Gnikyt\BasicShopifyAPI\BasicShopifyAPI;
use Gnikyt\BasicShopifyAPI\Test\BaseTest;
use Gnikyt\BasicShopifyAPI\Traits\IsResponseType;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class IsResponseTypeTest extends BaseTest
{
    public function test(): void
    {
        // Create anon class
        $klass = new class () {
            use IsResponseType;

            private $self;

            public function setSelf(TestCase $self): void
            {
                $this->self = $self;
            }

            public function testGraph(ResponseInterface $response): void
            {
                $this->self->assertTrue($this->isGraphResponse($response));
            }

            public function testRest(ResponseInterface $response): void
            {
                $this->self->assertTrue($this->isRestResponse($response));
            }
        };

        $klass->setSelf($this);
        $klass->testGraph(new Response());
        $klass->testRest(new Response(200, [BasicShopifyAPI::HEADER_REST_API_LIMITS => '39/40']));
    }
}
