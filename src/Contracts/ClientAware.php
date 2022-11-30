<?php

namespace Gnikyt\BasicShopifyAPI\Contracts;

use Gnikyt\BasicShopifyAPI\Options;
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
     * @return ClientInterface
     */
    public function getClient(): ClientInterface;

    /**
     * Set the options.
     *
     * @param Options $options
     *
     * @return void
     */
    public function setOptions(Options $options): void;

    /**
     * Get the options.
     *
     * @return Options
     */
    public function getOptions(): Options;
}
