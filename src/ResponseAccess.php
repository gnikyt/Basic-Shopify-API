<?php

namespace Osiset\BasicShopifyAPI;

use ArrayAccess;
use Countable;
use Iterator;
use JsonSerializable;

/**
 * Response data object for accessing.
 */
class ResponseAccess implements ArrayAccess, Iterator, Countable, JsonSerializable
{
    /**
     * The response data.
     *
     * @var mixed
     */
    public $container;

    /**
     * Position of iterator.
     *
     * @var int
     */
    public $position = 0;

    /**
     * Setup resource.
     *
     * @param mixed $data The data to use for source.
     *
     * @return self
     */
    final public function __construct($data)
    {
        $this->container = $data;
    }

    /**
     * Check if offset exists.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->container[$offset]);
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
        if ($offset === 'container') {
            return $this->container;
        }

        if (is_array($this->container[$offset])) {
            return new static($this->container[$offset]);
        }

        return $this->container[$offset];
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
        $this->container[$offset] = $value;
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
        unset($this->container[$offset]);
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
        return isset($this->container[$key]);
    }

    /**
     * Allows for accessing the underlying array as an object.
     * $response->shop->name will forward to $response['shop']['name'].
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->container[$key]) && is_array($this->container[$key])) {
            return new static($this->container[$key]);
        }

        return $this->container[$key];
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
        $this->container[$key] = $value;
    }

    /**
     * Rewind iterator.
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Get current position data.
     *
     * @return mixed
     */
    public function current()
    {
        if (is_array($this->container[$this->position])) {
            return new static($this->container[$this->position]);
        }

        return $this->container[$this->position];
    }

    /**
     * Current position.
     *
     * @return int
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Move position forward.
     *
     * @return void
     */
    public function next(): void
    {
        $this->position++;
    }

    /**
     * Check if valid iterator.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->container[$this->position]);
    }

    /**
     * Countable.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->container);
    }

    /**
     * Get keys for the array.
     *
     * @return array
     */
    public function keys(): array
    {
        return array_keys($this->container);
    }

    /**
     * Get values for the array.
     *
     * @return array
     */
    public function values(): array
    {
        return array_values($this->container);
    }

    /**
     * Return a JSON serializable array.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->container;
    }

    /**
     * To array, mainly for Laravel usage.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->container;
    }

    /**
     * Check if errors are in response.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return isset($this->container['errors']) || isset($this->container['error']);
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

        return $this->container['errors'] ?? $this->container['error'];
    }
}
