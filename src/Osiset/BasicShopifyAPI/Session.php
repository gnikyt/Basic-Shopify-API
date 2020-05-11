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
     * Setup a session.
     *
     * @param string $shop        The shop domain.
     * @param string $accessToken The access token for the shop.
     *
     * @return self
     */
    public function __construct(string $shop, string $accessToken)
    {
        $this->shop = $shop;
        $this->accessToken = $accessToken;
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
}
