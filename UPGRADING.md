# Upgrading

# v8.0.0 -> v9.0.0

Breaking changes, complete library rewrite.

While the requesting functions and responses are more-less the same, there's a change in initing the library.

### Initing

Before:

```php
use Osiset\BasicShopifyAPI;

$api = new BasicShopifyAPI([true/false], $options);
$api->set...();
```

Now:

```php
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\Options;

$options = new Options();
$options->set...();

$api = new BasicShopifyAPI($options);
```

### Responses

Responses previously was a `stdClass`, it is now an `array`.

`body` value of the response is now also an instance of `Osiset\BasicShopifyAPI\ResponseAccess` which allows for accessing the decoded JSON as an array or stdClass.

Example:

```php
$response = $api->rest('GET', '/admin/shop.json');
echo $response['body']['name'];
// or
echo $response['body']->name;
```

### Misc

- Rate limiting is built-in with defaults
- Retrying requests is now built-in using external middleware which will try requests at default of 2 times
- Shopify's new `X-Retry-After` is respected
- Shopify's new REST header for API call limits is respected

# v7.x.x -> v8.0.0

No upgrading required.

# v6.x.x -> v7.0.0

No upgrading required.

# v5.x.x -> v6.0.0

`errors` on the resulting object now returns a boolean instead of an object. The `body` will not contain the error response.

`rest` will remain the same, however, there is now `restAsync` which will return a Guzzle promise should you choose to use it.

# v4.x.x -> v5.x.x

No upgrading is required if you do not capture 400-500 exceptions from Guzzle yourself. If you do, the library now handles these exceptions internally and returns them inside the resulting object.

# v1.x.x -> v3.0.0

+ `getApiCalls()` now takes two arguments, first being rest|graph, second being the key

Old:

```php
getApiCalls('left');
```

New:

```php
getApiCalls('rest', 'left');
```

+ `request()` still exists, and is aliased to `rest()` but encourage you to move all REST calls to the new `rest()` method name