<?php

namespace Osiset\BasicShopifyAPI\Clients;

use Psr\Http\Message\ResponseInterface;
use Osiset\BasicShopifyAPI\Clients\AbstractClient;
use Osiset\BasicShopifyAPI\Contracts\GraphRequester;

/**
 * GraphQL client.
 */
class Graph extends AbstractClient implements GraphRequester
{
    /**
     * {@inheritDoc}
     *
     * @throws Exception When missing api password is missing for private apps.
     * @throws Exception When missing access key is missing for public apps.
     */
    public function request(string $query, array $variables = [], bool $sync = true)
    {
        /**
         * Run the request as sync or async
         */
        $requestFn = function (array $request) use ($sync) {
            // Encode the request
            $json = json_encode($request);

            // Run the request
            $fn = $sync ? 'request' : 'requestAsync';
            return $this->getClient()->{$fn}(
                'POST',
                $this->getBaseUri()->withPath('/admin/api/graphql.json'),
                ['body' => $json]
            );
        };

        // Build the request
        $request = ['query' => $query];
        if (count($variables) > 0) {
            $request['variables'] = $variables;
        }

        if ($sync === false) {
            // Async request
            $promise = $requestFn($request);
            return $promise->then([$this, 'handleResponse']);
        }

        // Sync request (default)
        return $this->handleResponse($requestFn($request));
    }

    /**
     * Handle response from request.
     *
     * @param ResponseInterface $resp
     *
     * @return array
     */
    public function handleResponse(ResponseInterface $resp): array
    {
        // Convert data to response
        $body = $this->toResponse($resp->getBody());

        // Return Guzzle response and JSON-decoded body
        return [
            'response'   => $resp,
            'body'       => $body,
            'errors'     => $body->hasErrors() ? $body->getErrors() : false,
            'timestamps' => $this->getTimeStore()->get(),
        ];
    }
}
