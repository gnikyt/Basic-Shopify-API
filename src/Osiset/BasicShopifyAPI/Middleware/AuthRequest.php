<?php

namespace Osiset\BasicShopifyAPI\Middleware;

use Exception;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\Options;
use Osiset\BasicShopifyAPI\Traits\IsRequestType;
use Psr\Http\Message\RequestInterface;

/**
 * Ensures we have the proper request for private and public calls.
 * Also modifies issues with redirects.
 */
class AuthRequest extends AbstractMiddleware
{
    use IsRequestType;

    /**
     * Run.
     *
     * @param callable $handler
     *
     * @throws Exception For missing API key or password for private apps.
     * @throws Exception For missing access token on GraphQL calls.
     *
     * @return callable
     */
    public function __invoke(callable $handler): callable
    {
        $self = $this;

        return function (RequestInterface $request, array $options) use ($self, $handler) {
            // Get the request URI
            $uri = $request->getUri();
            $isPrivate = $self->api->getOptions()->isPrivate();
            $apiKey = $self->api->getOptions()->getApiKey();
            $apiPassword = $self->api->getOptions()->getApiPassword();
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
                        $request = $request->withHeader(BasicShopifyAPI::HEADER_ACCESS_TOKEN, $accessToken);
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
                        BasicShopifyAPI::HEADER_ACCESS_TOKEN,
                        $apiPassword ?? $accessToken
                    );
                }
            }

            // Adjust URI path to be versioned
            $uri = $request->getUri();
            $request = $request->withUri(
                $uri->withPath(
                    $this->versionPath($uri->getPath())
                )
            );

            return $handler($request, $options);
        };
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
     * Versions the API call with the set version.
     *
     * @param string $uri The request URI.
     *
     * @return string
     */
    protected function versionPath(string $uri): string
    {
        $version = $this->api->getOptions()->getVersion();
        if ($version === null ||
            preg_match(Options::VERSION_PATTERN, $uri) ||
            !$this->isAuthableRequest($uri) ||
            !$this->isVersionableRequest($uri)
        ) {
            // No version set, or already versioned... nothing to do
            return $uri;
        }

        // Graph request
        if ($this->isGraphRequest($uri)) {
            return str_replace('/admin/api', "/admin/api/{$version}", $uri);
        }

        // REST request
        return preg_replace('/\/admin(\/api)?\//', "/admin/api/{$version}/", $uri);
    }

    /**
     * Determines if the request requires versioning.
     *
     * @param string $uri The request URI.
     *
     * @return bool
     */
    protected function isVersionableRequest(string $uri): bool
    {
        return preg_match('/\/admin\/(oauth\/access_scopes)/', $uri) === 0;
    }
}
