<?php

namespace NeoClocking\Exceptions;

use Exception;

class UserNotAuthorisedException extends Exception
{
    public function __construct($message = '')
    {
        parent::__construct($message, 1);
    }
}
