<?php

namespace Osiset\BasicShopifyAPI\Contracts;

use Exception;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Uri;
use Osiset\BasicShopifyAPI\ResponseAccess;

/**
 * Reprecents REST client.
 */
interface RestRequester extends LimitAccesser, TimeAccesser, SessionAware, ClientAware
{
    /**
     * Runs a request to the Shopify API.
     *
     * @param string     $type    The type of request... GET, POST, PUT, DELETE.
     * @param string     $path    The Shopify API path... /admin/xxxx/xxxx.json.
     * @param array|null $params  Optional parameters to send with the request.
     * @param array      $headers Optional headers to append to the request.
     * @param bool       $sync    Optionally wait for the request to finish.
     *
     * @throws Exception
     *
     * @return array|Promise
     */
    public function request(string $type, string $path, array $params = null, array $headers = [], bool $sync = true);

    /**
     * Gets the access object from a "code" supplied by Shopify request after successfull auth (for public apps).
     *
     * @param string $code The code from Shopify.
     *
     * @throws Exception When API secret is missing.
     *
     * @return ResponseAccess
     */
    public function requestAccess(string $code): ResponseAccess;

    /**
     * Returns the base URI to use.
     *
     * @throws Exception For missing shop domain.
     *
     * @return Uri
     */
    public function getBaseUri(): Uri;

    /**
     * Gets the auth URL for Shopify to allow the user to accept the app (for public apps).
     *
     * @param string|array $scopes      The API scopes as a comma seperated string or array.
     * @param string       $redirectUri The valid redirect URI for after acceptance of the permissions.
     *                                  It must match the redirect_uri in your app settings.
     * @param string       $mode        The API access mode, offline or per-user.
     *
     * @throws \Exception For missing API key.
     *
     * @return string Formatted URL.
     */
    public function getAuthUrl($scopes, string $redirectUri, string $mode = 'offline'): string;
}
