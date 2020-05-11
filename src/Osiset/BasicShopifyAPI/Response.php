<?php

namespace Osiset\BasicShopifyAPI;

use ArrayAccess;

/**
 * Response data object for accessing.
 */
class Response implements ArrayAccess
{
    /**
     * The response data.
     *
     * @var mixed
     */
    public $data;

    /**
     * Request timestamp for last and new call.
     *
     * @var array
     */
    protected $timestamps;

    /**
     * Setup resource.
     *
     * @param mixed $data       The data to use for source.
     * @param array $timestamps The timestamps of last and new request.
     *
     * @return self
     */
    public function __construct($data, array $timestamps = [])
    {
        $this->data = $data;
        $this->timestamps = $timestamps;
    }

    /**
     * Check if offset exists
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * Get the value by offset.
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * Set a value by offset.
     *
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->data[$offset] = $value;
    }

    /**
     * Remove by offset.
     *
     * @param mixed $offset
     *
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->resource[$offset]);
    }

    /**
     * Check if key exists in data.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Get to array.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->data[$key];
    }

    /**
     * Set to array.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function __set($key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Check if errors are in response.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return isset($this->data['errors']) || isset($this->data['error']);
    }

    /**
     * Get the errors.
     *
     * @return mixed
     */
    public function getErrors()
    {
        if (!$this->hasErrors()) {
            return;
        }

        return isset($this->data['errors']) ? $this->data['errors'] : $this->data['error'];
    }
}
