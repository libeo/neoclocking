<?php

namespace NeoClocking\Models;

/**
 * Generic class for storing an array of data with a simple get method
 * Class GenericData
 */
class GenericData
{
    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @param array $dataArray
     */
    public function __construct($dataArray)
    {
        if (is_array($dataArray)) {
            $this->attributes = $dataArray;
        }
    }

    /**
     * Check if this object contains any attributes
     *
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->attributes) === 0;
    }

    /**
     * Check if a given attribute is defined
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasAttribute($key)
    {
        return $this->get($key) !== null;
    }

    /**
     * @param String $key
     * @param mixed $default Value to return if not set
     *
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return $default;
    }

    /**
     * @param String $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->attributes[$key] = $value;
    }
}
