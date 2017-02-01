<?php

namespace NeoClocking\Repositories;

use NeoClocking\Exceptions\ModelValidationException;
use NeoClocking\Models\UserRole;
use NeoClocking\Validators\UserRoleValidator;

/**
 * Class UserRoleRepository
 * @package Repositories
 */
class UserRoleRepository
{

    /**
     * @param String $code
     * @return UserRole
     */
    public function findOneByCode($code)
    {
        return UserRole::query()->where('code', '=', $code)->first();
    }

    /**
     * @param UserRole $userRole
     * @return bool
     * @throws ModelValidationException
     */
    public function save(UserRole $userRole)
    {
        return $userRole->save();
    }
}
