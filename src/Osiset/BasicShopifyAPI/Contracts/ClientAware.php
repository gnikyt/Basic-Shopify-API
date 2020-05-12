<?php

namespace Osiset\BasicShopifyAPI\Contracts;

use GuzzleHttp\ClientInterface;

/**
 * Reprecents Guzzle client awareness.
 */
interface ClientAware
{
    /**
     * Set the Guzzle client.
     *
     * @param ClientInterface $client
     *
     * @return void
     */
    public function setClient(ClientInterface $client): void;

    /**
     * Get the client.
     *
     * @return ClientInterface|null
     */
    public function getClient(): ?ClientInterface;
}
