<?php

namespace Osiset;

use Closure;
use stdClass;
use Exception;
use Psr\Log\LogLevel;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Promise\Promise;
use Psr\Log\LoggerAwareInterface;
use Osiset\BasicShopifyAPI\Options;
use Osiset\BasicShopifyAPI\Session;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleRetry\GuzzleRetryMiddleware;
use Osiset\BasicShopifyAPI\Middleware\AuthRequest;

/**
 * Basic Shopify API for REST & GraphQL.
 *
 * Note: Single file due to the nature of the project.
 * Code was originally small, with just basic REST support.
 * Now, it supports link headers, GraphQL, rate limiting, etc.
 * In the future, we will try to seperate this class for better
 * maintainability, right now we do not see a way without breaking
 * changes occruing.
 */
class BasicShopifyAPI
{
    /**
     * The Guzzle client.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * The library options.
     *
     * @var Options
     */
    protected $options;

    /**
     * The API session.
     *
     * @var Session
     */
    protected $session;

    /**
     * Request timestamp for every new call.
     * Used for rate limiting.
     *
     * @var int
     */
    protected $requestTimestamp;

    /**
     * Constructor.
     *
     * @param Options $options The options for the library setup.
     *
     * @return self
     */
    public function __construct(Options $options)
    {
        // Set the options
        $this->options = $options;

        // Create the stack and assign the middleware which attempts to fix redirects
        $stack = HandlerStack::create();
        $stack->push((new AuthRequest($this))());
        $stack->push(GuzzleRetryMiddleware::factory());

        // Create a default Guzzle client with our stack
        $this->client = new Client($this->options->getGuzzleOptions());
    }

    /**
     * Sets the Guzzle client for the API calls (allows for override with your own).
     *
     * @param \GuzzleHttp\Client $client The Guzzle client
     *
     * @return self
     */
    public function setClient(Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Set options for the library.
     *
     * @param Options $options
     *
     * @return self
     */
    public function setOptions(Options $options): self
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Get the options for the library.
     *
     * @return Options
     */
    public function getOptions(): Options
    {
        return $this->options;
    }

    /**
     * Set the session for the API calls.
     *
     * @param Session $session The shop/user session.
     *
     * @return self
     */
    public function setSession(Session $session): self
    {
        $this->session = $session;
        return $this;
    }

    /**
     * Get the session.
     *
     * @return Session|null
     */
    public function getSession(): ?Session
    {
        return $this->session;
    }

    /**
     * Accepts a closure to do isolated API calls for a shop.
     *
     * @param Session $session The shop/user session.
     *
     * @throws Exception When closure is missing or not callable.
     *
     * @return mixed
     */
    public function withSession(Session $session, Closure $closure)
    {
        // Clone the API class and bind it to the closure
        $clonedApi = clone $this;
        $clonedApi->setSession($session);
        return $closure->call($clonedApi);
    }

    /**
     * Returns the base URI to use.
     *
     * @throws Exception for missing shop domain.
     *
     * @return Uri
     */
    public function getBaseUri(): Uri
    {
        if ($this->shop === null) {
            // Shop is required
            throw new Exception('Shopify domain missing for API calls');
        }

        return new Uri("https://{$this->shop}");
    }

    /**
     * Gets the auth URL for Shopify to allow the user to accept the app (for public apps).
     *
     * @param string|array $scopes      The API scopes as a comma seperated string or array.
     * @param string       $redirectUri The valid redirect URI for after acceptance of the permissions.
     *                                  It must match the redirect_uri in your app settings.
     * @param string|null  $mode        The API access mode, offline or per-user.
     *
     * @throws Exception for missing API key.
     *
     * @return string Formatted URL.
     */
    public function getAuthUrl($scopes, string $redirectUri, string $mode = 'offline'): string
    {
        if ($this->apiKey === null) {
            throw new Exception('API key is missing');
        }

        if (is_array($scopes)) {
            $scopes = implode(',', $scopes);
        }

        $query = [
            'client_id'    => $this->apiKey,
            'scope'        => $scopes,
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
     * Verify the request is from Shopify using the HMAC signature (for public apps).
     *
     * @param array $params The request parameters (ex. $_GET).
     *
     * @throws Exception for missing API secret.
     *
     * @return bool If the HMAC is validated.
     */
    public function verifyRequest(array $params): bool
    {
        if ($this->apiSecret === null) {
            // Secret is required
            throw new Exception('API secret is missing');
        }

        // Ensure shop, timestamp, and HMAC are in the params
        if (array_key_exists('shop', $params)
            && array_key_exists('timestamp', $params)
            && array_key_exists('hmac', $params)
        ) {
            // Grab the HMAC, remove it from the params, then sort the params for hashing
            $hmac = $params['hmac'];
            unset($params['hmac']);
            ksort($params);

            // Encode and hash the params (without HMAC), add the API secret, and compare to the HMAC from params
            return $hmac === hash_hmac('sha256', urldecode(http_build_query($params)), $this->apiSecret);
        }

        // Not valid
        return false;
    }

    /**
     * Gets the access object from a "code" supplied by Shopify request after successfull auth (for public apps).
     *
     * @param string $code The code from Shopify.
     *
     * @throws Exception When API secret is missing.
     *
     * @return stdClass The access object.
     */
    public function requestAccess(string $code): stdClass
    {
        if ($this->apiSecret === null || $this->apiKey === null) {
            // Key and secret required
            throw new Exception('API key or secret is missing');
        }

        // Do a JSON POST request to grab the access token
        $request = $this->client->request(
            'POST',
            $this->getBaseUri()->withPath('/admin/oauth/access_token'),
            [
                'json' => [
                    'client_id'     => $this->apiKey,
                    'client_secret' => $this->apiSecret,
                    'code'          => $code,
                ],
            ]
        );

        // Decode the response body
        $body = json_decode($request->getBody());
        $this->log('RequestAccess response: '.json_encode($body));

        return $body;
    }

    /**
     * Gets the access token from a "code" supplied by Shopify request after successfull auth (for public apps).
     *
     * @param string $code The code from Shopify.
     *
     * @return string The access token.
     */
    public function requestAccessToken(string $code): string
    {
        return $this->requestAccess($code)->access_token;
    }

    /**
     * Gets the access object from a "code" and sets it to the instance (for public apps).
     *
     * @param string $code The code from Shopify.
     *
     * @return void
     */
    public function requestAndSetAccess(string $code): void
    {
        $access = $this->requestAccess($code);

        // Set the access token
        $this->setAccessToken($access->access_token);

        if (property_exists($access, 'associated_user')) {
            // Set the user if applicable
            $this->setUser($access->associated_user);
            $this->log('User access: '.json_encode($access->associated_user));
        }
    }

    /**
     * Alias for REST method for backwards compatibility.
     *
     * @see rest
     */
    public function request()
    {
        return call_user_func_array([$this, 'rest'], func_get_args());
    }

    /**
     * Returns the current API call limits.
     *
     * @param string|null $key The key to grab (left, made, limit, etc).
     *
     * @throws Exception When attempting to grab a key that doesn't exist.
     *
     * @return mixed Either whole array of call data or single key.
     */
    public function getApiCalls(string $type = 'rest', string $key = null)
    {
        if ($key) {
            $keys = array_keys($this->apiCallLimits[$type]);
            if (!in_array($key, $keys)) {
                // No key like that in array
                throw new Exception('Invalid API call limit key. Valid keys are: '.implode(', ', $keys));
            }

            // Return the key value requested
            return $this->apiCallLimits[$type][$key];
        }

        // Return all the values
        return $this->apiCallLimits[$type];
    }

    /**
     * Runs a request to the Shopify API.
     *
     * @param string $query     The GraphQL query.
     * @param array  $variables The optional variables for the query.
     * @param bool   $sync      Optionally wait for the request to finish.
     *
     * @throws Exception When missing api password is missing for private apps.
     * @throws Exception When missing access key is missing for public apps.
     *
     * @return stdClass|Promise An Object of the Guzzle response, and JSON-decoded body.
     */
    public function graph(string $query, array $variables = [], bool $sync = true)
    {
        // Request function
        $requestFn = function (array $request) use ($sync) {
            // Encode the request
            $json = json_encode($request);
            $this->log("Graph request: {$json}");

            // Update the timestamp of the request
            $this->updateRequestTime();

            // Run the request
            $fn = $sync ? 'request' : 'requestAsync';
            return $this->client->{$fn}(
                'POST',
                $this->getBaseUri()->withPath(
                    $this->versionPath('/admin/api/graphql.json')
                ),
                ['body' => $json]
            );
        };

        /**
         * Success function.
         *
         * @param ResponseInterface $resp The response object.
         *
         * @return stdClass
         */
        $successFn = function (ResponseInterface $resp): stdClass {
            // Grab the data result and extensions
            $rawBody = $resp->getBody();
            $body = $this->jsonDecode($rawBody);
            $bodyArray = $this->jsonDecode($rawBody, true);
            $tmpTimestamp = $this->updateGraphCallLimits($body);

            $this->log('Graph response: '.json_encode(property_exists($body, 'errors') ? $body->errors : $body->data));

            // Return Guzzle response and JSON-decoded body
            return (object) [
                'response'   => $resp,
                'body'       => property_exists($body, 'errors') ? $body->errors : $body->data,
                'bodyArray'  => isset($bodyArray['errors']) ? $bodyArray['errors'] : $bodyArray['data'],
                'errors'     => property_exists($body, 'errors'),
                'timestamps' => [$tmpTimestamp, $this->requestTimestamp],
            ];
        };

        // Build the request
        $request = ['query' => $query];
        if (count($variables) > 0) {
            $request['variables'] = $variables;
        }

        if ($sync === false) {
            // Async request
            $promise = $requestFn($request);
            return $promise->then($successFn);
        } else {
            // Sync request (default)
            return $successFn($requestFn($request));
        }
    }

    /**
     * Runs a request to the Shopify API (async).
     *
     * @see graph
     */
    public function graphAsync(string $query, array $variables = []): Promise
    {
        return $this->graph($query, $variables, false);
    }

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
     * @return stdClass|Promise An Object of the Guzzle response, and JSON-decoded body OR a promise.
     */
    public function rest(string $type, string $path, array $params = null, array $headers = [], bool $sync = true)
    {
        // Check the rate limit before firing the request
        $this->handleRateLimiting();

        // Update the timestamp of the request
        $tmpTimestamp = $this->updateRequestTime();

        // Build URI and try the request
        $uri = $this->getBaseUri()->withPath($this->versionPath($path));

        // Build the request parameters for Guzzle
        $guzzleParams = [];
        if ($params !== null) {
            $guzzleParams[strtoupper($type) === 'GET' ? 'query' : 'json'] = $params;
        }

        $this->log("[{$uri}:{$type}] Request Params: ".json_encode($params));

        // Add custom headers
        if (count($headers) > 0) {
            $guzzleParams['headers'] = $headers;
            $this->log("[{$uri}:{$type}] Request Headers: ".json_encode($headers));
        }

        // Request function
        $requestFn = function () use ($sync, $type, $uri, $guzzleParams) {
            $fn = $sync ? 'request' : 'requestAsync';
            return $this->client->{$fn}($type, $uri, $guzzleParams);
        };

        /**
         * Success function.
         *
         * @param ResponseInterface $resp The response object.
         *
         * @return stdClass
         */
        $successFn = function (ResponseInterface $resp) use ($uri, $type, $tmpTimestamp): stdClass {
            $rawBody = $resp->getBody();
            $status = $resp->getStatusCode();

            $this->updateRestCallLimits($resp);
            $this->log("[{$uri}:{$type}] {$status}: ".json_encode($rawBody));

            // Check for "Link" header
            $link = null;
            if ($resp->hasHeader('Link')) {
                $link = $this->extractLinkHeader($resp->getHeader('Link')[0]);
            }

            // Return Guzzle response and JSON-decoded body
            return (object) [
                'errors'     => false,
                'status'     => $status,
                'response'   => $resp,
                'body'       => $this->jsonDecode($rawBody),
                'bodyArray'  => $this->jsonDecode($rawBody, true),
                'link'       => $link,
                'timestamps' => [$tmpTimestamp, $this->requestTimestamp],
            ];
        };

        /**
         * Error function.
         *
         * @param RequestException $e The request exception object.
         *
         * @return stdClass
         */
        $errorFn = function (RequestException $e) use ($uri, $type, $tmpTimestamp): stdClass {
            $resp = $e->getResponse();
            if ($resp) {
                $rawBody = $resp->getBody();
                $status = $resp->getStatusCode();

                $this->updateRestCallLimits($resp);
                $this->log("[{$uri}:{$type}] {$status} Error: {$rawBody}");

                // Build the error object
                $body = $this->jsonDecode($rawBody);
                $bodyArray = $this->jsonDecode($rawBody, true);
                if ($body !== null) {
                    if (property_exists($body, 'errors')) {
                        $body = $body->errors;
                        $bodyArray = $bodyArray['errors'];
                    } elseif (property_exists($body, 'error')) {
                        $body = $body->error;
                        $bodyArray = $bodyArray['error'];
                    } else {
                        $body = null;
                        $bodyArray = null;
                    }
                }
            } else {
                $status = null;
                $body = null;
                $bodyArray = null;

                $this->log("[{$uri}:{$type}] Unknown Error: {$e->getMessage()}");
            }

            return (object) [
                'errors'     => true,
                'status'     => $status,
                'response'   => $resp,
                'body'       => $body,
                'bodyArray'  => $bodyArray,
                'link'       => null,
                'exception'  => $e,
                'timestamps' => [$tmpTimestamp, $this->requestTimestamp],
            ];
        };

        if ($sync === false) {
            // Async request
            $promise = $requestFn();
            return $promise->then($successFn, $errorFn);
        } else {
            // Sync request (default)
            try {
                return $successFn($requestFn());
            } catch (RequestException $e) {
                return $errorFn($e);
            }
        }
    }

    /**
     * Runs a request to the Shopify API (async).
     * Alias for `rest` with `sync` param set to `false`.
     *
     * @see rest
     */
    public function restAsync(string $type, string $path, array $params = null, array $headers = []): Promise
    {
        return $this->rest($type, $path, $params, $headers, false);
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

    /**
     * Versions the API call with the set version.
     *
     * @param string $uri The request URI.
     *
     * @return string
     */
    protected function versionPath(string $uri): string
    {
        if ($this->version === null ||
            preg_match(self::VERSION_PATTERN, $uri) ||
            !$this->isAuthableRequest($uri) ||
            !$this->isVersionableRequest($uri)
        ) {
            // No version set, or already versioned... nothing to do
            return $uri;
        }

        // Graph request
        if ($this->isGraphRequest($uri)) {
            return str_replace('/admin/api', "/admin/api/{$this->version}", $uri);
        }

        // REST request
        return preg_replace('/\/admin(\/api)?\//', "/admin/api/{$this->version}/", $uri);
    }

    /**
     * Handles rate limiting (if enabled).
     *
     * @return void
     */
    protected function handleRateLimiting(): void
    {
        if (!$this->isRateLimitingEnabled() || !$this->requestTimestamp) {
            return;
        }

        // Calculate in milliseconds the duration the API call took
        $duration = round(microtime(true) - $this->requestTimestamp, 3) * 1000;
        $waitTime = ($this->rateLimitCycle - $duration) + $this->rateLimitCycleBuffer;

        if ($waitTime > 0) {
            // Do the sleep for X mircoseconds (convert from milliseconds)
            $this->log('Rest rate limit hit');
            usleep($waitTime * 1000);
        }
    }

    /**
     * Updates the request time.
     *
     * @return float|null
     */
    protected function updateRequestTime(): ?float
    {
        $tmpTimestamp = $this->requestTimestamp;
        $this->requestTimestamp = microtime(true);

        return $tmpTimestamp;
    }

    /**
     * Updates the REST API call limits from Shopify headers.
     *
     * @param ResponseInterface $resp The response from the request.
     *
     * @return void
     */
    protected function updateRestCallLimits(ResponseInterface $resp): void
    {
        // Grab the API call limit header returned from Shopify
        $callLimitHeader = $resp->getHeader('http_x_shopify_shop_api_call_limit');
        if (!$callLimitHeader) {
            return;
        }

        $calls = explode('/', $callLimitHeader[0]);
        $this->apiCallLimits['rest'] = [
            'left'  => (int) $calls[1] - $calls[0],
            'made'  => (int) $calls[0],
            'limit' => (int) $calls[1],
        ];
    }

    /**
     * Updates the GraphQL API call limits from the response body.
     *
     * @param stdClass $body The GraphQL response body.
     *
     * @return void
     */
    protected function updateGraphCallLimits(stdClass $body): void
    {
        if (!property_exists($body, 'extensions') || !property_exists($body->extensions, 'cost')) {
            return;
        }

        // Update the API call information
        $calls = $body->extensions->cost;
        $this->apiCallLimits['graph'] = [
            'left'          => (int)
                $calls->throttleStatus->currentlyAvailable,
            'made'          => (int)
                ($calls->throttleStatus->maximumAvailable - $calls->throttleStatus->currentlyAvailable),
            'limit'         => (int)
                $calls->throttleStatus->maximumAvailable,
            'restoreRate'   => (int)
                $calls->throttleStatus->restoreRate,
            'requestedCost' => (int)
                $calls->requestedQueryCost,
            'actualCost'    => (int)
                $calls->actualQueryCost,
        ];
    }

    /**
     * Processes the "Link" header.
     *
     * @return stdClass
     */
    protected function extractLinkHeader(string $header): stdClass
    {
        $links = [
            'next'     => null,
            'previous' => null,
        ];
        $regex = '/<.*page_info=([a-z0-9\-_]+).*>; rel="?{type}"?/i';

        foreach (array_keys($links) as $type) {
            preg_match(str_replace('{type}', $type, $regex), $header, $matches);
            $links[$type] = isset($matches[1]) ? $matches[1] : null;
        }

        return (object) $links;
    }
}
