<?php

namespace NeoClocking\Services\Ldap;

/**
 * A class to allow the storing of LDAP Conditions
 * In a more logical manner than an array
 *
 * Class LdapCondition
 *
 * @package Libeo\NeoClocking\Libraries
 */
class LdapCondition
{
    /**
     * @var String
     */
    private $attribute;

    /**
     * @var String
     */
    private $operator;

    /**
     * @var String
     */
    private $value;

    /**
     * @param String $attribute
     * @param String $operator
     * @param String $value
     */
    public function __construct($attribute, $operator = "=", $value = "*")
    {
        $this->attribute = $attribute;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * @return String
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @param String $attribute
     */
    public function setAttribute($attribute)
    {
        $this->attribute = $attribute;
    }

    /**
     * @return String
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param String $operator
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
    }

    /**
     * @return String
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param String $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
