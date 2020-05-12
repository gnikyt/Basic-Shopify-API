<?php

namespace Osiset\BasicShopifyAPI\Clients;

use Psr\Http\Message\ResponseInterface;
use Osiset\BasicShopifyAPI\Clients\AbstractClient;
use Osiset\BasicShopifyAPI\Contracts\GraphRequester;
use Osiset\BasicShopifyAPI\Response;

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
    protected $limits = [
        'left'          => 0,
        'made'          => 0,
        'limit'         => 1000,
        'restoreRate'   => 50,
        'requestedCost' => 0,
        'actualCost'    => 0,
    ];

    /**
     * Last actual cost of a query/mutation.
     *
     * @var int|null
     */
    protected $lastActualCost;

    /**
     * {@inheritDoc}
     */
    public function setLimits(array $limits): void
    {
        $this->limits = $limits;
    }

    /**
     * {@inheritDoc}
     */
    public function getLimits(): array
    {
        return $this->limits;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception When missing api password is missing for private apps.
     * @throws Exception When missing access key is missing for public apps.
     */
    public function request(string $query, array $variables = [], bool $sync = true)
    {
        // Request function
        $requestFn = function (array $request) use ($sync) {
            // Encode the request
            $json = json_encode($request);

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

    protected function handleSuccess(ResponseInterface $resp): array
    {
        // Convert data to response
        $body = $this->toResponse($resp->getBody());

        // Return Guzzle response and JSON-decoded body
        return [
            'response'   => $resp,
            'body'       => $body,
            'errors'     => $body->hasErrors() ? $body->getErrors() : false,
            'timestamps' => [$tmpTimestamp, $this->requestTimestamp],
        ];
    }

    protected function handleFailure()
    {

    }
}
