<?php

namespace NeoClocking\Exceptions;

use Exception;

class JsonHttpException extends Exception
{

    /**
     * @var array
     */
    private $messages = [];

    /**
     * @var integer
     */
    private $statusCode = 400;

    public function __construct($messages = [], $statusCode = 400, $code = 0)
    {
        $this->statusCode = $statusCode;

        if (is_array($messages)) {
            $this->messages = $messages;
        } else {
            $this->messages[] = $messages;
        }

        parent::__construct('', $code);
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
