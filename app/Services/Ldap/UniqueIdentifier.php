<?php

namespace NeoClocking\Services\Ldap;

trait UniqueIdentifier
{
    /**
     * Read the dn and return the unique identifier.
     *
     * @param  string $dn
     * @return string
     */
    protected function getUid($dn)
    {
        $dn = explode(',', $dn);
        $uid = array_shift($dn);
        $uid = explode('=', $uid);

        return array_pop($uid);
    }
}
