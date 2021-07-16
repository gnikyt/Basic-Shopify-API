<?php

namespace Osiset\BasicShopifyAPI\Clients;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Osiset\BasicShopifyAPI\Contracts\RestRequester;
use Osiset\BasicShopifyAPI\ResponseAccess;
use Psr\Http\Message\ResponseInterface;

/**
 * REST handler.
 */
class Rest extends AbstractClient implements RestRequester
{
    /**
     * Processes the "Link" header.
     *
     * @return ResponseAccess
     */
    protected function extractLinkHeader(string $header): ResponseAccess
    {
        $links = [
            'next' => null,
            'previous' => null,
        ];
        $regex = '/<.*page_info=([a-z0-9\-_]+).*>; rel="?{type}"?/i';

        foreach (array_keys($links) as $type) {
            preg_match(str_replace('{type}', $type, $regex), $header, $matches);
            $links[$type] = $matches[1] ?? null;
        }

        return new ResponseAccess($links);
    }

    /**
     * {@inheritdoc}
     */
    public function requestAccess(string $code): ResponseAccess
    {
        if ($this->getOptions()->getApiSecret() === null || $this->getOptions()->getApiKey() === null) {
            // Key and secret required
            throw new Exception('API key or secret is missing');
        }

        // Do a JSON POST request to grab the access token
        $url = $this->getBaseUri()->withPath('/admin/oauth/access_token');
        $data = [
            'json' => [
                'client_id' => $this->getOptions()->getApiKey(),
                'client_secret' => $this->getOptions()->getApiSecret(),
                'code' => $code,
            ],
        ];

        try {
            $response = $this->getClient()->request(
                'POST',
                $url,
                $data
            );
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents());

            throw new Exception($body->error_description);
        }

        return $this->toResponse($response->getBody());
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthUrl($scopes, string $redirectUri, string $mode = 'offline'): string
    {
        if ($this->getOptions()->getApiKey() === null) {
            throw new Exception('API key is missing');
        }

        if (is_array($scopes)) {
            $scopes = implode(',', $scopes);
        }

        $query = [
            'client_id' => $this->getOptions()->getApiKey(),
            'scope' => $scopes,
            'redirect_uri' => $redirectUri,
        ];
        if ($mode !== null && $mode !== 'offline') {
            $query['grant_options'] = [$mode];
        }

        return (string) $this
            ->getBaseUri()
            ->withPath('/admin/oauth/authorize')
            ->withQuery(
                preg_replace('/\%5B\d+\%5D/', '%5B%5D', http_build_query($query))
            );
    }

    /**
     * {@inheritdoc}
     */
    public function request(string $type, string $path, array $params = null, array $headers = [], bool $sync = true)
    {
        // Build URI
        $uri = $this->getBaseUri()->withPath($path);

        // Build the request parameters for Guzzle
        $guzzleParams = [];
        if ($params !== null) {
            $keys = array_keys($params);
            if (isset($keys[0]) && in_array($keys[0], ['query', 'json'])) {
                // Inputted type
                $guzzleParams = $params;
            } else {
                // Detect type
                $guzzleParams[strtoupper($type) === 'GET' ? 'query' : 'json'] = $params;
            }
        }

        // Add custom headers
        if (count($headers) > 0) {
            $guzzleParams['headers'] = $headers;
        }

        /**
         * Run the request as sync or async.
         */
        $requestFn = function () use ($sync, $type, $uri, $guzzleParams) {
            $fn = $sync ? 'request' : 'requestAsync';

            return $this->getClient()->{$fn}($type, $uri, $guzzleParams);
        };

        if ($sync === false) {
            // Async request
            $promise = $requestFn();

            return $promise->then([$this, 'handleSuccess'], [$this, 'handleFailure']);
        }

        // Sync request (default)
        try {
            return $this->handleSuccess($requestFn());
        } catch (RequestException $e) {
            return $this->handleFailure($e);
        }
    }

    /**
     * Handle success of response.
     *
     * @param ResponseInterface $resp
     *
     * @return array
     */
    public function handleSuccess(ResponseInterface $resp): array
    {
        // Check for "Link" header
        $link = null;
        if ($resp->hasHeader('Link')) {
            $link = $this->extractLinkHeader($resp->getHeader('Link')[0]);
        }

        // Return Guzzle response and JSON-decoded body
        return [
            'errors' => false,
            'response' => $resp,
            'status' => $resp->getStatusCode(),
            'body' => $this->toResponse($resp->getBody()),
            'link' => $link,
            'timestamps' => $this->getTimeStore()->get($this->getSession()),
        ];
    }

    /**
     * Handle failure of response.
     *
     * @param RequestException $e
     *
     * @return array
     */
    public function handleFailure(RequestException $e): array
    {
        $resp = $e->getResponse();
        $body = null;
        $status = null;

        if ($resp) {
            // Get the body stream
            $rawBody = $resp->getBody();
            $status = $resp->getStatusCode();

            // Build the error object
            if ($rawBody !== null) {
                // Convert data to response
                $body = $this->toResponse($rawBody);
                $body = $body->hasErrors() ? $body->getErrors() : null;
            }
        }

        return [
            'errors' => true,
            'response' => $resp,
            'status' => $status,
            'body' => $body,
            'link' => null,
            'exception' => $e,
            'timestamps' => $this->getTimeStore()->get($this->getSession()),
        ];
    }
}
