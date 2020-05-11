<?php

namespace Osiset\BasicShopifyAPI\Middleware;

use Exception;
use Psr\Http\Message\RequestInterface;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;

class AuthRequest
{
    /**
     * The API instance.
     *
     * @var BasicShopifyAPI
     */
    protected $api;

    /**
     * Setup.
     *
     * @param BasicShopifyAPI $api The API instance.
     *
     * @return self
     */
    public function __construct(BasicShopifyAPI $api)
    {
        $this->api = $api;
    }

    /**
     * Determines if the request requires auth headers.
     *
     * @param string $uri The request URI.
     *
     * @return bool
     */
    protected function isAuthableRequest(string $uri): bool
    {
        return preg_match('/\/admin\/oauth\/(authorize|access_token)/', $uri) === 0;
    }

    /**
     * Determines if the request is to Graph API.
     *
     * @param string $uri The request URI.
     *
     * @return bool
     */
    protected function isGraphRequest(string $uri): bool
    {
        return strpos($uri, 'graphql.json') !== false;
    }

    /**
     * Determines if the request is to REST API.
     *
     * @param string $uri The request URI.
     *
     * @return bool
     */
    protected function isRestRequest(string $uri): bool
    {
        return $this->isGraphRequest($uri) === false;
    }

    /**
     * Ensures we have the proper request for private and public calls.
     * Also modifies issues with redirects.
     *
     * @throws Exception for missing API key or password for private apps.
     * @throws Exception for missing access token on GraphQL calls.
     *
     * @return callable
     */
    public function __invoke(): callable
    {
        $self = $this;
        return function (callable $handler) use ($self) {
            return function (RequestInterface $request, array $options) use ($self, $handler) {
                // Get the request URI
                $uri = $request->getUri();
                $isPrivate = $self->api->geOptions()->isPrivate();
                $apiKey = $self->api->getSession()->getApiKey();
                $apiPassword = $self->api->getSession()->getApiPassword();
                $accessToken = $self->api->getSession()->getAccessToken();

                if ($self->isAuthableRequest((string) $uri)) {
                    if ($self->isRestRequest((string) $uri)) {
                        // Checks for REST
                        if ($isPrivate && ($apiKey === null || $apiPassword === null)) {
                            // Key and password are required for private API calls
                            throw new Exception('API key and password required for private Shopify REST calls');
                        }

                        if ($isPrivate) {
                            // Private: Add auth for REST calls, add the basic auth header
                            $request = $request->withHeader(
                                'Authorization',
                                'Basic '.base64_encode("{$apiKey}:{$apiPassword}")
                            );
                        } else {
                            // Public: Add the token header
                            $request = $request->withHeader('X-Shopify-Access-Token', $accessToken);
                        }
                    } else {
                        // Checks for Graph
                        if ($isPrivate && ($apiPassword === null && $accessToken === null)) {
                            // Private apps need password for use as access token
                            throw new Exception('API password/access token required for private Shopify GraphQL calls');
                        } elseif (!$isPrivate && $accessToken === null) {
                            // Need access token for public calls
                            throw new Exception('Access token required for public Shopify GraphQL calls');
                        }

                        // Public/Private: Add the token header
                        $request = $request->withHeader(
                            'X-Shopify-Access-Token',
                            $apiPassword ?? $accessToken
                        );
                    }
                }

                return $handler($request, $options);
            };
        };
    }
}
