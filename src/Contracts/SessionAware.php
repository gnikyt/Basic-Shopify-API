<?php

namespace Gnikyt\BasicShopifyAPI\Contracts;

use Gnikyt\BasicShopifyAPI\Session;

/**
 * Reprecents session awareness.
 */
interface SessionAware
{
    /**
     * Set the session for the API calls.
     *
     * @param Session $session The shop/user session.
     *
     * @return void
     */
    public function setSession(Session $session): void;

    /**
     * Get the session.
     *
     * @return Session|null
     */
    public function getSession(): ?Session;
}
