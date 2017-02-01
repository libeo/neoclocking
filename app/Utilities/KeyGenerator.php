<?php

namespace NeoClocking\Utilities;

class KeyGenerator
{
    /**
     * Generate a relatively random 22 character string
     *
     * @return string
     */
    public static function generateRandomKey()
    {
        return bin2hex(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM));
    }
}
