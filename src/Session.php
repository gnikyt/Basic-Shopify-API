<?php

namespace Osiset\BasicShopifyAPI;

/**
 * Shop or user session.
 */
class Session
{
    /**
     * The Shopify domain.
     *
     * @var string|null
     */
    protected $shop;

    /**
     * The Shopify access token.
     *
     * @var string|null
     */
    protected $accessToken;

    /**
     * If the API was called with per-user grant option, this will be filled.
     *
     * @var ResponseAccess|null
     */
    protected $user;

    /**
     * Setup a session.
     *
     * @param string              $shop        The shop domain.
     * @param string|null         $accessToken The access token for the shop.
     * @param ResponseAccess|null $user        The user for per-user.
     *
     * @return self
     */
    public function __construct(string $shop, ?string $accessToken = null, ?ResponseAccess $user = null)
    {
        $this->shop = $shop;
        $this->accessToken = $accessToken;
        $this->user = $user instanceof ResponseAccess && count($user->keys()) > 0 ? $user : null;
    }

    /**
     * Gets the access token.
     *
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    /**
     * Gets the Shopify domain (*.myshopify.com) we're working with.
     *
     * @return string|null
     */
    public function getShop(): ?string
    {
        return $this->shop;
    }

    /**
     * Gets the user.
     *
     * @return ResponseAccess|null
     */
    public function getUser(): ?ResponseAccess
    {
        return $this->user;
    }

    /**
     * Checks if we have a user.
     *
     * @return bool
     */
    public function hasUser(): bool
    {
        return $this->user !== null;
    }
}
