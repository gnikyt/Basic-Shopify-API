<?php

namespace Osiset\BasicShopifyAPI;

/**
 * Options for the library.
 */
class Options
{
    /**
     * Private or public API calls.
     *
     * @var bool
     */
    protected $private = false;

    /**
     * The Shopify API key.
     *
     * @var string|null
     */
    protected $apiKey;

    /**
     * The Shopify API password.
     *
     * @var string|null
     */
    protected $apiPassword;

    /**
     * The Shopify API secret.
     *
     * @var string|null
     */
    protected $apiSecret;

    /**
     * How many requests allowed per second.
     *
     * @var int
     */
    protected $restLimit = 2;

    /**
     * How many points allowed to use per second.
     *
     * @var int
     */
    protected $graphLimit = 50;

    /**
     * Additional Guzzle options.
     *
     * @var array
     */
    protected $guzzleOptions = [
        'headers'                  => [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ],
        'timeout'                  => 10.0,
        'max_retry_attempts'       => 2,
        'default_retry_multiplier' => 2.0,
        'retry_on_status'          => [429, 503, 500],
    ];

    /**
     * Set type for API calls.
     *
     * @param bool $private True for private, false for public.
     *
     * @return self
     */
    public function setType(bool $private): self
    {
        $this->private = $private;
        return $this;
    }

    /**
     * Get the type for API calls.
     *
     * @return bool
     */
    public function getType(): bool
    {
        return $this->private;
    }

    /**
     * Determines if the calls are private.
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->private === true;
    }

    /**
     * Determines if the calls are public.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return !$this->isPrivate();
    }

    /**
     * Sets the API key for use with the Shopify API (public or private apps).
     *
     * @param string $apiKey The API key.
     *
     * @return self
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * Get the API key.
     *
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * Sets the API secret for use with the Shopify API (public apps).
     *
     * @param string $apiSecret The API secret key.
     *
     * @return self
     */
    public function setApiSecret(string $apiSecret): self
    {
        $this->apiSecret = $apiSecret;
        return $this;
    }

    /**
     * Get the API secret.
     *
     * @return string|null
     */
    public function getApiSecret(): ?string
    {
        return $this->apiSecret;
    }

    /**
     * Sets the API password for use with the Shopify API (private apps).
     *
     * @param string $apiPassword The API password.
     *
     * @return self
     */
    public function setApiPassword(string $apiPassword): self
    {
        $this->apiPassword = $apiPassword;
        return $this;
    }

    /**
     * Get API password.
     *
     * @return string|null
     */
    public function getApiPassword(): ?string
    {
        return $this->apiPassword;
    }

    /**
     * Set the REST limit.
     *
     * @param int $limit
     *
     * @return self
     */
    public function setRestLimit(int $limit): self
    {
        $this->restLimit = $limit;
        return $this;
    }

    /**
     * Get the REST limit.
     *
     * @return integer
     */
    public function getRestLimit(): int
    {
        return $this->restLimit;
    }

    /**
     * Set the GraphQL limit.
     *
     * @param int $limit
     *
     * @return self
     */
    public function setGraphLimit(int $limit): self
    {
        $this->graphLimit = $limit;
        return $this;
    }

    /**
     * Get the GraphQL limit.
     *
     * @return integer
     */
    public function getGraphLimit(): int
    {
        return $this->graphLimit;
    }

    /**
     * Set options for Guzzle.
     *
     * @param array $options
     *
     * @return self
     */
    public function setGuzzleOptions(array $options): self
    {
        $this->guzzleOptions = array_merge($this->guzzleOptions, $options);
        return $this;
    }

    /**
     * Get options for Guzzle.
     *
     * @return array
     */
    public function getGuzzleOptions(): array
    {
        return $this->guzzleOptions;
    }
}
