<?php

namespace Osiset\BasicShopifyAPI\Clients;

use Osiset\BasicShopifyAPI\Response;
use Psr\Http\Message\StreamInterface;
use Osiset\BasicShopifyAPI\Contracts\TimeStorer;
use Osiset\BasicShopifyAPI\Contracts\TimeTracker;
use Osiset\BasicShopifyAPI\Contracts\TimeDeferrer;

/**
 * Base client class.
 */
abstract class AbstractClient
{
    /**
     * The time store implementation.
     *
     * @var TimeStorer
     */
    protected $tstore;

    /**
     * The time deferrer implementation.
     *
     * @var TimeDeferrer
     */
    protected $tdeferrer;

    /**
     * Setup.
     *
     * @param TimeStorer   $tstore    The time store implementation.
     * @param TimeDeferrer $tdeferrer The time deferrer implementation.
     *
     * @return self
     */
    public function __construct(TimeStorer $tstore, TimeDeferrer $tdeferrer)
    {
        $this->tstore = $tstore;
        $this->tdeferrer = $tdeferrer;
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeDeferrer(): TimeDeferrer
    {
        return $this->tdeferrer;
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeStore(): TimeStorer
    {
        return $this->tstore;
    }

    /**
     * Convert request response to response object.
     *
     * @return Response
     */
    public function toResponse(StreamInterface $body): Response
    {
        $decoded = json_decode($body, true, 512, JSON_BIGINT_AS_STRING);
        return new Response($decoded);
    }
}
