<?php

namespace NeoClocking\Exceptions;

use Exception;

class SkipRowImportException extends Exception
{
    /**
     * @var string
     */
    private $rowType;

    /**
     * @var bool
     */
    private $silent = false;

    public function __construct($rowType, $message)
    {
        $this->rowType = $rowType;
        parent::__construct($message);
    }

    /**
     * @return string
     */
    public function getRowType()
    {
        return $this->rowType;
    }

    /**
     * @return bool
     */
    public function getSilent()
    {
        return $this->silent;
    }

    /**
     * @param bool $silent
     */
    public function setSilent($silent)
    {
        $this->silent = $silent;
    }
}
