<?php

namespace Osiset\BasicShopifyAPI\Clients;

use Exception;
use GuzzleHttp\Psr7\Uri;
use Osiset\BasicShopifyAPI\Session;
use Osiset\BasicShopifyAPI\Contracts\TimeStorer;
use Osiset\BasicShopifyAPI\Contracts\LimitTracker;
use Osiset\BasicShopifyAPI\Contracts\SessionAware;
use Osiset\BasicShopifyAPI\Contracts\TimeAccesser;
use Osiset\BasicShopifyAPI\Contracts\TimeDeferrer;
use Osiset\BasicShopifyAPI\Traits\ResponseTransform;

/**
 * Base client class.
 */
abstract class AbstractClient implements TimeAccesser, SessionAware, LimitTracker
{
    use ResponseTransform;

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
     * The API session.
     *
     * @var Session|null
     */
    protected $session;

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
    public function getBaseUri(): Uri
    {
        if ($this->session->getShop() === null) {
            // Shop is required
            throw new Exception('Shopify domain missing for API calls');
        }

        return new Uri("https://{$this->shop}");
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
     * {@inheritDoc}
     */
    public function setSession(Session $session): void
    {
        $this->session = $session;
    }

    /**
     * {@inheritDoc}
     */
    public function getSession(): ?Session
    {
        return $this->session;
    }

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
}
