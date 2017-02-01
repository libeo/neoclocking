<?php

namespace NeoClocking\Services\LibeoDap;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class Response implements Arrayable, Jsonable, ArrayAccess
{
    protected $attributes = [];

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function __get($key)
    {
        return array_get($this->attributes, $key);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this-$this->toArray(), $options);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($key)
    {
        return array_has($this->attributes, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($key)
    {
        return array_get($this->attributes, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        array_set($this->attributes, $key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($key)
    {
        unset($this->attributes[$key]);
    }
}
