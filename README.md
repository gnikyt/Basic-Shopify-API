# Basic Shopify API

![Tests](https://github.com/osiset/Basic-Shopify-API/workflows/Package%20Test/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/osiset/Basic-Shopify-API/branch/master/graph/badge.svg?token=qqUuLItqJj)](https://codecov.io/gh/osiset/Basic-Shopify-API)
[![License](https://poser.pugx.org/osiset/basic-shopify-api/license)](https://packagist.org/packages/osiset/basic-shopify-api)

A simple, tested, API wrapper for Shopify using Guzzle.

It supports both the sync/async REST and GraphQL API provided by Shopify, basic rate limiting, and request retries.

It contains helpful methods for generating a installation URL, an authorize URL (offline and per-user), HMAC signature validation, call limits, and API requests.

It works with both OAuth and private API apps.

## Table of Contents
  * [Installation](#installation)
  * [Usage](#usage)
      * [Public API](#public-api)
        * [REST (sync)](#rest-sync)
        * [REST (async)](#rest-async)
        * [GraphQL (sync)](#graphql-sync)
        * [GraphQL (async)](#graphql-async)
        * [Getting access (offline)](#getting-access-offline)
        * [Getting access (per-user)](#getting-access-per-user)
        * [Verifying HMAC signature](#verifying-hmac-signature)
      * [Private API](#private-api)
        * [REST](#rest)
        * [GraphQL](#graphql)
      * [Making requests](#making-requests)
        * [REST](#rest-1)
            * [If sync is true (regular rest call)](#if-sync-is-true-regular-rest-call)
            * [If sync is false (restAsync call)](#if-sync-is-false-restasync-call)
            * [Overriding request type](#overriding-request-type)
            * [Passing additional request options](#passing-additional-request-options)
        * [GraphQL](#graphql-1)
      * [API Versioning](#api-versioning)
      * [Rate Limiting](#rate-limiting)
      * [page_info / pagination Support](#page_info--pagination-support)
      * [Isolated API calls](#isolated-api-calls)
      * [Retries](#retries)
      * [Errors](#errors)
      * [Middleware](#middleware)
      * [Storage](#storage)
  * [Documentation](#documentation)
  * [LICENSE](#license)

## Installation

The recommended way to install is [through composer](http://packagist.org).

    composer require osiset/basic-shopify-api

## Usage

### Public API

This assumes you properly have your app setup in the partner's dashboard with the correct keys and redirect URIs.

#### REST (sync)

For REST calls, the shop domain and access token are required.

```php
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\Options;
use Osiset\BasicShopifyAPI\Session;

// Create options for the API
$options = new Options();
$options->setVersion('2020-01');

// Create the client and session
$api = new BasicShopifyAPI($options);
$api->setSession(new Session('example.myshopify.com', 'access-token-here'));

// Now run your requests...
$result = $api->rest(...);
```

#### REST (async)

For REST calls, the shop domain and access token are required.

```php
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\Options;
use Osiset\BasicShopifyAPI\Session;

// Create options for the API
$options = new Options();
$options->setVersion('2020-01');

// Create the client and session
$api = new BasicShopifyAPI($options);
$api->setSession(new Session('example.myshopify.com', 'access-token-here'));

// Now run your requests...
$promise = $api->restAsync(...);
$promise->then(function (array $result) {
  // ...
});
```

#### GraphQL (sync)

For GraphQL calls, the shop domain and access token are required.

```php
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\Options;
use Osiset\BasicShopifyAPI\Session;

// Create options for the API
$options = new Options();
$options->setVersion('2020-01');

// Create the client and session
$api = new BasicShopifyAPI($options);
$api->setSession(new Session('example.myshopify.com', 'access-token-here'));

// Now run your requests...
$result = $api->graph(...);
```

#### GraphQL (async)

For GraphQL calls, the shop domain and access token are required.

```php
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\Options;
use Osiset\BasicShopifyAPI\Session;

// Create options for the API
$options = new Options();
$options->setVersion('2020-01');

// Create the client and session
$api = new BasicShopifyAPI($options);
$api->setSession(new Session('example.myshopify.com', 'access-token-here'));

// Now run your requests...
$promise = $api->graphAsync(...);
$promise->then(function (array $result) {
  // ...
});
```

#### Getting access (offline)

This is the default mode which returns a permanent token.
 
After obtaining the user's shop domain, to then direct them to the auth screen use `getAuthUrl`, as example (basic PHP):

```php
// Create options for the API
$options = new Options();
$options->setVersion('2020-01');
$options->setApiKey(env('SHOPIFY_API_KEY'));
$options->setApiSecret(env('SHOPIFY_API_SECRET'));

// Create the client and session
$api = new BasicShopifyAPI($options);
$api->setSession(new Session($_SESSION['shop']));

$code = $_GET['code'];
if (!$code) {
  /**
   * No code, send user to authorize screen
   * Pass your scopes as an array for the first argument
   * Pass your redirect URI as the second argument
   */
  $redirect = $api->getAuthUrl(env('SHOPIFY_API_SCOPES'), env('SHOPIFY_API_REDIRECT_URI'));
  header("Location: {$redirect}");
  exit;
} else {
  // We now have a code, lets grab the access token
  $api->requestAndSetAccess($code);

  // You can now make API calls
  $request = $api->rest('GET', '/admin/shop.json'); // or GraphQL
}
```

#### Getting access (per-user)

You can also change the grant mode to be `per-user` as [outlined in Shopify documentation](https://help.shopify.com/en/api/getting-started/authentication/oauth/api-access-modes). This will receieve user info from the user of the app within the Shopify store. The token recieved will expire at a specific time.

```php
// Create options for the API
$options = new Options();
$options->setVersion('2020-01');
$options->setApiKey(env('SHOPIFY_API_KEY'));
$options->setApiSecret(env('SHOPIFY_API_SECRET'));

// Create the client and session
$api = new BasicShopifyAPI($options);
$api->setSession(new Session($_SESSION['shop']));

$code = $_GET['code'];
if (!$code) {
  /**
   * No code, send user to authorize screen
   * Pass your scopes as an array for the first argument
   * Pass your redirect URI as the second argument
   * Pass your grant mode as the third argument
   */
  $redirect = $api->getAuthUrl(env('SHOPIFY_API_SCOPES'), env('SHOPIFY_API_REDIRECT_URI'), 'per-user');
  header("Location: {$redirect}");
  exit;
} else {
  // We now have a code, lets grab the access object
  $api->requestAndSetAccess($code);

  // You can now make API calls
  $request = $api->rest('GET', '/admin/shop.json'); // or GraphQL
}
```

#### Verifying HMAC signature

Simply pass in an array of GET params.

```php
// Will return true or false if HMAC signature is good.
$valid = $api->verifyRequest($_GET);
```

### Private API

This assumes you properly have your app setup in the partner's dashboard with the correct keys and redirect URIs.

#### REST

For REST (sync) calls, shop domain, API key, and API password are request

```php
// Create options for the API
$options = new Options();
$options->setType(true); // Makes it private
$options->setVersion('2020-01');
$options->setApiKey(env('SHOPIFY_API_KEY'));
$options->setApiPassword(env('SHOPIFY_API_PASSWORD'));

// Create the client and session
$api = new BasicShopifyAPI($options);
$api->setSession(new Session($_SESSION['shop']));

// Now run your requests...
$result = $api->rest(...);
```

#### GraphQL

For GraphQL calls, shop domain and API password are required.

```php
// Create options for the API
$options = new Options();
$options->setVersion('2020-01');
$options->setApiPassword(env('SHOPIFY_API_PASSWORD'));

// Create the client and session
$api = new BasicShopifyAPI($options);
$api->setSession(new Session($_SESSION['shop']));

// Now run your requests...
$result = $api->graph(...);
```

### Making requests

#### REST

Requests are made using Guzzle.

```php
$api->rest(string $type, string $path, array $params = null, array $headers = [], bool $sync = true);
// or $api->getRestClient()->request(....);
```

+ `type` refers to GET, POST, PUT, DELETE, etc
+ `path` refers to the API path, example: `/admin/products/1920902.json`
+ `params` refers to an array of params you wish to pass to the path, examples: `['handle' => 'cool-coat']`
+ `headers` refers to an array of custom headers you would like to optionally send with the request, example: `['X-Shopify-Test' => '123']`
+ `sync` refers to if the request should be synchronous or asynchronous.

You can use the alias `restAsync` to skip setting `sync` to `false`.

##### If sync is true (regular rest call):

The return value for the request will be an array containing:

+ `response` the full Guzzle response object
+ `body` the JSON decoded response body (\Osiset\BasicShopifyAPI\ResponseAccess instance)
+ `errors` if any errors are detected, true/false
+ `exception` if errors are true, exception object is available
+ `status` the HTTP status code
+ `link` an array of previous/next pagination values, if available

*Note*: `request()` will alias to `rest()` as well.

##### If sync is false (restAsync call):

The return value for the request will be a Guzzle promise which you can handle on your own.

The return value for the promise will be an object containing:

+ `response` the full Guzzle response object
+ `body` the JSON decoded response body (\Osiset\BasicShopifyAPI\ResponseAccess instance)
+ `errors` if any errors are detected, true/false
+ `exception` if errors are true, exception object is available
+ `status` the HTTP status code
+ `link` an array of previous/next pagination values, if available

```php
$promise = $api->restAsync(...);
$promise->then(function (array $result) {
  // `response` and `body`, etc are available in `$result`.
});
```

##### Overriding request type

If you require the need to force a query string for example on a non-GET endpoint, you can specify the type as a key.

```php
$api->rest('PUT', '/admin/themes/12345/assets.json', ['query' => [...]]);
```

Valid keys are `query` and `json`.

##### Passing additional request options

If you'd like to pass additional request options to the Guzzle client created, pass them as the second argument of the constructor.

```php
// Create options for the API
$options = new Options();
$options->setVersion('2020-01');
// ...
$options->setGuzzleOptions(['connect_timeout' => 3.0]);

// Create the client
$api = new BasicShopifyApi($options);
```

#### GraphQL

Requests are made using Guzzle.

```php
$api->graph(string $query, array $variables = []);
```

+ `query` refers to the full GraphQL query
+ `variables` refers to the variables used for the query (if any)

The return value for the request will be an object containing:

+ `response` the full Guzzle response object
+ `body` the JSON decoded response body (\Osiset\BasicShopifyAPI\ResponseAccess instance)
+ `errors` if there was errors or not, will return the errors if any are found
+ `status` the HTTP status code

Example query:

```php
$result = $api->graph('{ shop { product(first: 1) { edges { node { handle, id } } } } }');
echo $result['body']['shop']['products']['edges'][0]['node']['handle']; // test-product
// or echo $result['body']->shop->products->edges[0]->node->handle;
```

Example mutation:

```php
$result = $api->graph(
    'mutation collectionCreate($input: CollectionInput!) { collectionCreate(input: $input) { userErrors { field message } collection { id } } }',
    ['input' => ['title' => 'Test Collection']]
);
echo $result['body']['collectionCreate']['collection']['id']; // gid://shopify/Collection/63171592234
// or echo $result['body']->collectionCreate->collection->id;
```

### API Versioning

This library supports [versioning the requests](https://www.shopify.com/partners/blog/api-versioning-at-shopify), example:

```php
// Create options for the API
$options = new Options();
$options->setVersion('2020-01'); // YYYY-MM or "unstable" is accepted

// Create the client
$api = new BasicShopifyAPI($options);
```

You can override the versioning at anytime for specific API requests, example:

```php
// Create options for the API
$options = new Options();
$options->setVersion('2020-01');

// Create the client
$api = new BasicShopifyAPI($options);
$api->rest('GET', '/admin/api/unstable/shop.json'); // Will ignore "2020-01" version and use "unstable" for this request
```

### Rate limiting

This library comes with a built-in basic rate limiter which utilizes `usleep` between applicable calls.

* For REST: it ensures you do not request more than the default of 2 calls per second.
* For GraphQL: it ensures you do not use more than the default of 50 points per second.

To adjust the default limits, use the option class' `setRestLimit` and `setGraphLimit`.

#### Custom rate limiting

You simply need to disable the built-in rate limiter and push in a custom Guzzle middleware. Example:

```php
$options = new Options();
// ...
$options->disableRateLimiting();

// ...
$api = new BasicShopifyAPI($options);
$api->addMiddleware(new CustomRateLimiter($api), 'rate:limiting');
```

### page_info / pagination support

2019-07 API version introduced a new `Link` header which is used for pagination ([explained here](https://help.shopify.com/en/api/guides/paginated-rest-results)).

If an endpoint supports page_info, you can use `$response->link` to grab the page_info value to pass in your next request.

Example:

```php
$response = $api->rest('GET', '/admin/products.json', ['limit' => 5]);
$link = $response['link']['next']; // eyJsYXN0X2lkIjo0MDkw
$link2 = $response['link']['previous']; // dkUIsk00wlskWKl
$response = $api->rest('GET', '/admin/products.json', ['limit' => 5, 'page_info' => $link]);
```

### Isolated API calls

You can initialize the API once and use it for multiple shops. Each instance will be contained to not pollute the others. This is useful for something like background job processing.

```php
$api->withSession(Session $newSession, Closure $closure);
```

`$this` will be binded to the closure. Example:

```php
$api->withSession(new Session('someshop.myshopify.com', 'some-token'), function (): void {
  $request = $this->rest('GET', '/admin/shop.json');
  echo $request['body']['shop']['name']; // Some Shop
});

// $api->rest/graph will not be affected by the above code, it will use previously defined session
```

### Retries

This library utilizes `caseyamcl/guzzle_retry_middleware` middleware package.

By default, `429`, '500` and `503` errors will be retried twice.

For REST calls, it will utilize Shopify's `X-Retry-After` header to wait *x* seconds before retrying the call.

When all retries are exhasted, the standard response from the library will return where you can handle the error.

To change the status codes watched or the maximum number of retries, use the option class' `setGuzzleOptions`:

```php
// Create options for the API
$options = new Options();
$options->setVersion('2020-01');
// ...
$options->setGuzzleOptions(
  'max_retry_attempts' => 3, // Was 2
  'retry_on_status'    => [429, 503, 400], // Was 439, 503, 500
);

// Create the client
$api = new BasicShopifyApi($options);
```

### Errors

This library internally catches only 400-500 status range errors through Guzzle. You're able to check for an error of this type and get its response status code and body.

```php
$call = $api->rest('GET', '/admin/non-existant-route-or-object.json');

if ($call['errors']) {
  echo "Oops! {$call['status']} error";
  var_dump($call['body']);

  // Original exception can be accessed via `$call['exception']`
  // Example, if response body was `{"error": "Not found"}`...
  /// then: `$call['body']` would return "Not Found"
}
```

### Middleware

This library takes advantage of using Guzzle middleware for request/response checks and modifications. You're also able to inject middleware.

```php
$api->addMiddleware([callable]);
```

See Guzzle's documentation on middleware. As well, you can browse this library's middleware for examples.

### Storage

For storing the current request times, API limits, request costs, etc. A basic in-memory array store is used `Osiset\BasicShopifyAPI\Store\Memory`.

If you would like to implement a more advananced store such as one with Redis, simply implement `Osiset\BasicShopifyAPI\Contracts\StateStorage` and set the client to use it, example:

```php
$timeStore = new RedisStore();
$limitStore = new RedisStore();

$api = new BasicShopifyAPI($options, $timeStore, $limitStore);
```

## Documentation

Code documentation is [available here](https://osiset.com/Basic-Shopify-API) from phpDocumentor via `phpdoc -d src -t doc`.

## LICENSE

This project is released under the MIT [license](https://github.com/osiset/Basic-Shopify-API/blob/master/LICENSE).

## Misc

Using Python? [Check out basic_shopify_api](https://github.com/osiset/basic_shopify_api).
