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
     * @param string      $shop        The shop domain.
     * @param string|null $accessToken The access token for the shop.
     * @param mixed|null  $user        The user for per-user.
     *
     * @return self
     */
    public function __construct(string $shop, ?string $accessToken = null, $user = null)
    {
        $this->shop = $shop;
        $this->accessToken = $accessToken;

        $associated_user = isset($user['associated_user']) ? $user['associated_user'] : null;

        if ($associated_user) {
            $associated_user['associated_user_scope'] = isset($user['associated_user_scope']) ? $user['associated_user_scope'] : null;
            $associated_user['expires_in'] = isset($user['expires_in']) ? $user['expires_in'] : null;
            $associated_user['session'] = isset($user['session']) ? $user['session'] : null;
            $associated_user['account_number'] = isset($user['account_number']) ? $user['account_number'] : null;

            $this->user = $associated_user instanceof ResponseAccess ? $associated_user : new ResponseAccess($associated_user);
        }
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
