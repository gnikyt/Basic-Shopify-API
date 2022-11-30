<?php

namespace Gnikyt\BasicShopifyAPI\Test\Middleware;

use GuzzleHttp\Psr7\Request;
use Gnikyt\BasicShopifyAPI\BasicShopifyAPI;
use Gnikyt\BasicShopifyAPI\Middleware\AuthRequest;
use Gnikyt\BasicShopifyAPI\Options;
use Gnikyt\BasicShopifyAPI\Session;
use Gnikyt\BasicShopifyAPI\Test\BaseTest;
use Psr\Http\Message\RequestInterface;

class AuthRequestTest extends BaseTest
{
    public function testRestVersioningOfApiPath(): void
    {
        // Create options
        $opts = (new Options())->setVersion('2020-01');

        // Use callback handler to test result
        $callMw = call_user_func(
            $this->buildMw($opts),
            function (RequestInterface $request, array $options): void {
                $this->assertSame(
                    '/admin/api/2020-01/shop.json',
                    (string) $request->getUri()
                );
            }
        );

        $callMw(new Request('GET', '/admin/shop.json'), []);
    }

    public function testGraphVersioningOfApiPath(): void
    {
        // Create options
        $opts = (new Options())->setVersion('2020-01');

        // Use callback handler to test result
        $callMw = call_user_func(
            $this->buildMw($opts),
            function (RequestInterface $request, array $options): void {
                $this->assertSame(
                    '/admin/api/2020-01/graphql.json',
                    (string) $request->getUri()
                );
            }
        );

        $callMw(new Request('GET', '/admin/api/graphql.json'), []);
    }

    public function testRestNotVersioningApiPath(): void
    {
        // Create options
        $opts = (new Options())->setVersion('2020-01');

        // Use callback handler to test result
        $callMw = call_user_func(
            $this->buildMw($opts),
            function (RequestInterface $request, array $options): void {
                $this->assertSame(
                    '/admin/api/2019-04/shop.json',
                    (string) $request->getUri()
                );
            }
        );

        $callMw(new Request('GET', '/admin/api/2019-04/shop.json'), []);
    }

    public function testGraphNotVersioningApiPath(): void
    {
        // Create options
        $opts = (new Options())->setVersion('2020-01');

        // Use callback handler to test result
        $callMw = call_user_func(
            $this->buildMw($opts),
            function (RequestInterface $request, array $options): void {
                $this->assertSame(
                    '/admin/api/2019-04/graphql.json',
                    (string) $request->getUri()
                );
            }
        );

        $callMw(new Request('GET', '/admin/api/2019-04/graphql.json'), []);
    }

    public function testRestShouldSendAccessTokenHeader(): void
    {
        // Create options
        $opts = (new Options())->setVersion('2020-01');

        // Use callback handler to test result
        $callMw = call_user_func(
            $this->buildMw($opts),
            function (RequestInterface $request, array $options): void {
                $this->assertTrue($request->hasHeader(BasicShopifyAPI::HEADER_ACCESS_TOKEN));
            }
        );

        $callMw(new Request('GET', '/admin/shop.json'), []);
    }

    public function testRestSendPrivateAuthHeader(): void
    {
        // Create options
        $opts = (new Options())
            ->setType(true)
            ->setApiKey('abc123')
            ->setApiPassword('xyz123');

        // Use callback handler to test result
        $callMw = call_user_func(
            $this->buildMw($opts),
            function (RequestInterface $request, array $options): void {
                $this->assertTrue($request->hasHeader('Authorization'));
            }
        );

        $callMw(new Request('GET', '/admin/shop.json'), []);
    }

    public function testGraphSendAccessTokenHeader(): void
    {
        // Create options
        $opts = (new Options())->setType(true);

        // Use callback handler to test result
        $callMw = call_user_func(
            $this->buildMw($opts),
            function (RequestInterface $request, array $options): void {
                $this->assertTrue($request->hasHeader(BasicShopifyAPI::HEADER_ACCESS_TOKEN));
            }
        );

        $callMw(new Request('GET', '/admin/api/graphql.json'), []);
    }

    public function buildMw(Options $options): AuthRequest
    {
        // Create the client
        $api = $this->buildClient([], function (Options $opts) use ($options): Options {
            return $options;
        });
        $api->setSession(new Session('example.myshopify.com', 'abc123'));

        // Create the middleware instance
        return new AuthRequest($api);
    }
}
