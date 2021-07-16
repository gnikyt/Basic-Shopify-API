<?php

namespace Osiset\BasicShopifyAPI;

use Closure;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Promise;
use GuzzleRetry\GuzzleRetryMiddleware;
use Osiset\BasicShopifyAPI\Clients\Graph;
use Osiset\BasicShopifyAPI\Clients\Rest;
use Osiset\BasicShopifyAPI\Contracts\ClientAware;
use Osiset\BasicShopifyAPI\Contracts\GraphRequester;
use Osiset\BasicShopifyAPI\Contracts\RestRequester;
use Osiset\BasicShopifyAPI\Contracts\SessionAware;
use Osiset\BasicShopifyAPI\Contracts\StateStorage;
use Osiset\BasicShopifyAPI\Contracts\TimeDeferrer;
use Osiset\BasicShopifyAPI\Deferrers\Sleep;
use Osiset\BasicShopifyAPI\Middleware\AuthRequest;
use Osiset\BasicShopifyAPI\Middleware\RateLimiting;
use Osiset\BasicShopifyAPI\Middleware\UpdateApiLimits;
use Osiset\BasicShopifyAPI\Middleware\UpdateRequestTime;
use Osiset\BasicShopifyAPI\Store\Memory;
use Osiset\BasicShopifyAPI\Traits\ResponseTransform;

/**
 * Basic Shopify API for REST & GraphQL.
 */
class BasicShopifyAPI implements SessionAware, ClientAware
{
    use ResponseTransform;

    /**
     * Header for per-shop API call limits (recieve).
     *
     * @var string
     */
    public const HEADER_REST_API_LIMITS = 'http_x_shopify_shop_api_call_limit';

    /**
     * Header for access token (send).
     *
     * @var string
     */
    public const HEADER_ACCESS_TOKEN = 'x-shopify-access-token';

    /**
     * The Guzzle client.
     *
     * @var ClientInterface
     */
    protected $client;

    /**
     * The handler stack.
     *
     * @var HandlerStack
     */
    protected $stack;

    /**
     * The GraphQL client.
     *
     * @var GraphRequester
     */
    protected $graphClient;

    /**
     * The REST client.
     *
     * @var RestRequester
     */
    protected $restClient;

    /**
     * The library options.
     *
     * @var Options
     */
    protected $options;

    /**
     * The API session.
     *
     * @var Session|null
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
     * @param Options           $options   The options for the library setup.
     * @param StateStorage|null $tstore    The time storer implementation to use for rate limiting.
     * @param StateStorage|null $lstore    The limits storer implementation to use for rate limiting.
     * @param TimeDeferrer|null $tdeferrer The time deferrer implementation to use for rate limiting.
     *
     * @return self
     */
    public function __construct(
        Options $options,
        ?StateStorage $tstore = null,
        ?StateStorage $lstore = null,
        ?TimeDeferrer $tdeferrer = null
    ) {
        // Setup REST and GraphQL clients
        $this->setupClients($tstore, $lstore, $tdeferrer);

        // Set the options
        $this->setOptions($options);

        // Create the stack and assign the middleware which attempts to fix redirects
        $this->stack = HandlerStack::create($this->getOptions()->getGuzzleHandler());
        $this
            ->addMiddleware(new AuthRequest($this), 'request:auth')
            ->addMiddleware(new UpdateApiLimits($this), 'rate:update')
            ->addMiddleware(new UpdateRequestTime($this), 'time:update')
            ->addMiddleware(GuzzleRetryMiddleware::factory(), 'request:retry');
        if ($this->getOptions()->isRateLimitingEnabled()) {
            $this->addMiddleware(new RateLimiting($this), 'rate:limiting');
        }

        // Create a default Guzzle client with our stack
        $this->setClient(
            new Client(array_merge(
                ['handler' => $this->stack],
                $this->getOptions()->getGuzzleOptions()
            ))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setClient(ClientInterface $client): void
    {
        $this->client = $client;
        $this->getGraphClient()->setClient($this->client);
        $this->getRestClient()->setClient($this->client);
    }

    /**
     * {@inheritdoc}
     */
    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(Options $options): void
    {
        $this->options = $options;
        $this->getGraphClient()->setOptions($this->options);
        $this->getRestClient()->setOptions($this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): Options
    {
        return $this->options;
    }

    /**
     * Sets the GraphQL request client.
     *
     * @param GraphRequester $client The client for GraphQL.
     *
     * @return self
     */
    public function setGraphClient(GraphRequester $client): self
    {
        $this->graphClient = $client;

        return $this;
    }

    /**
     * Get the GraphQL client.
     *
     * @return GraphRequester
     */
    public function getGraphClient(): GraphRequester
    {
        return $this->graphClient;
    }

    /**
     * Sets the REST request client.
     *
     * @param RestRequester $client The client for REST.
     *
     * @return self
     */
    public function setRestClient(RestRequester $client): self
    {
        $this->restClient = $client;

        return $this;
    }

    /**
     * Get the REST client.
     *
     * @return RestRequester
     */
    public function getRestClient(): RestRequester
    {
        return $this->restClient;
    }

    /**
     * {@inheritdoc}
     */
    public function setSession(Session $session): void
    {
        $this->session = $session;
        $this->getGraphClient()->setSession($this->session);
        $this->getRestClient()->setSession($this->session);
    }

    /**
     * {@inheritdoc}
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
     * Add middleware to the handler stack.
     *
     * @param callable $callable Middleware function.
     * @param string   $name     Name to register for this middleware.
     *
     * @return self
     */
    public function addMiddleware(callable $callable, string $name = ''): self
    {
        $this->stack->push($callable, $name);

        return $this;
    }

    /**
     * Remove middleware to the handler stack.
     *
     * @param string $name Name to register for this middleware.
     *
     * @return self
     */
    public function removeMiddleware(string $name = ''): self
    {
        $this->stack->remove($name);

        return $this;
    }

    /**
     * @see Rest::getAuthUrl
     */
    public function getAuthUrl($scopes, string $redirectUri, string $mode = 'offline'): string
    {
        return $this->getRestClient()->getAuthUrl($scopes, $redirectUri, $mode);
    }

    /**
     * @see Rest::requestAccess
     */
    public function requestAccess(string $code): ResponseAccess
    {
        return $this->getRestClient()->requestAccess($code);
    }

    /**
     * Gets the access token from a "code" supplied by Shopify request after successfull auth (for public apps).
     *
     * @param string $code The code from Shopify.
     *
     * @return string
     */
    public function requestAccessToken(string $code): string
    {
        return $this->requestAccess($code)['access_token'];
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
        // Get the access response data
        $access = $this->requestAccess($code);

        // Setup the additional user data (if available)
        $user = [];
        if (isset($access['associated_user'])) {
            $keys = ['associated_user', 'associated_user_scope', 'expires_in', 'session', 'account_number'];
            foreach ($keys as $key) {
                $user[$key] = $access[$key] ?? null;
            }
        }

        $session = new Session(
            $this->session->getShop(),
            $access['access_token'],
            new ResponseAccess($user)
        );

        // Update the session
        $this->setSession($session);
    }

    /**
     * Verify the request is from Shopify using the HMAC signature (for public apps).
     *
     * @param array $params The request parameters (ex. $_GET).
     *
     * @throws Exception For missing API secret.
     *
     * @return bool If the HMAC is validated.
     */
    public function verifyRequest(array $params): bool
    {
        if ($this->getOptions()->getApiSecret() === null) {
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
            return $hmac === hash_hmac(
                'sha256',
                urldecode(http_build_query($params)),
                $this->options->getApiSecret()
            );
        }

        // Not valid
        return false;
    }

    /**
     * Alias for REST method for backwards compatibility.
     *
     * @see rest
     */
    public function request()
    {
        return call_user_func_array(
            [$this, 'rest'],
            func_get_args()
        );
    }

    /**
     * @see Graph::request
     */
    public function graph(string $query, array $variables = [], bool $sync = true)
    {
        return $this->getGraphClient()->request($query, $variables, $sync);
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
     * @see Rest::request
     */
    public function rest(string $type, string $path, array $params = null, array $headers = [], bool $sync = true)
    {
        return $this->getRestClient()->request($type, $path, $params, $headers, $sync);
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
     * Setup the REST and GraphQL clients.
     *
     * @param StateStorage|null $tstore    The time storer implementation to use for rate limiting.
     * @param StateStorage|null $lstore    The limits storer implementation to use for rate limiting.
     * @param TimeDeferrer|null $tdeferrer The time deferrer implementation to use for rate limiting.
     *
     * @return void
     */
    protected function setupClients(
        ?StateStorage $tstore = null,
        ?StateStorage $lstore = null,
        ?TimeDeferrer $tdeferrer = null
    ): void {
        // Base/default storage class if none provided
        $baseStorage = Memory::class;

        // Setup timestamp storage
        $graphTstore = $tstore === null ? new $baseStorage() : clone $tstore;
        $restTstore = $tstore === null ? new $baseStorage() : clone $tstore;

        // Setup limits storage
        $graphLstore = $lstore === null ? new $baseStorage() : clone $lstore;
        $restLstore = $lstore === null ? new $baseStorage() : clone $lstore;

        // Setup time deferrer
        $tdeferrer = $tdeferrer ?? new Sleep();

        // Setup REST and Graph clients
        $this->setRestClient(new Rest($restTstore, $restLstore, $tdeferrer));
        $this->setGraphClient(new Graph($graphTstore, $graphLstore, $tdeferrer));
    }
}
