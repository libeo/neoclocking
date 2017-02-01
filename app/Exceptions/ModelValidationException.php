<?php

namespace NeoClocking\Exceptions;

use Illuminate\Support\MessageBag;
use RuntimeException;

class ModelValidationException extends RuntimeException
{
    /**
     * @var MessageBag
     */
    protected $validationErrors;

    /**
     * @return MessageBag
     */
    public function getValidationErrors()
    {
        return $this->validationErrors;
    }

    /**
     * @param MessageBag $validationErrors
     */
    public function setValidationErrors(MessageBag $validationErrors)
    {
        $this->validationErrors = $validationErrors;
    }
}
