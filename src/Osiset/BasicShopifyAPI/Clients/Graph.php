<?php

namespace Osiset\BasicShopifyAPI\Clients;

use Osiset\BasicShopifyAPI\Clients\AbstractClient;
use Osiset\BasicShopifyAPI\Contracts\GraphRequester;

/**
 * GraphQL client.
 */
class Graph extends AbstractClient implements GraphRequester
{
    /**
     * The current API call limits from last request.
     *
     * @var array
     */
    protected $apiCallLimits = [
        'left'          => 0,
        'made'          => 0,
        'limit'         => 1000,
        'restoreRate'   => 50,
        'requestedCost' => 0,
        'actualCost'    => 0,
    ];

    /**
     * Request timestamp for every new call.
     * Used for rate limiting.
     *
     * @var int
     */
    protected $requestTimestamp;

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
    public function request(string $query, array $variables = [], bool $sync = true)
    {
        // Request function
        $requestFn = function (array $request) use ($sync) {
            // Encode the request
            $json = json_encode($request);

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
}
