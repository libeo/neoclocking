<?php

namespace NeoClocking\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use NeoClocking\Exceptions\ModelValidationException;
use NeoClocking\Models\Project;
use NeoClocking\Models\User;
use NeoClocking\Models\UserRole;

/**
 * User's data
 * No create or save here since Data is copied from LDAP
 */
class UserRepository
{

    /**
     * @return \Illuminate\Database\Eloquent\Collection|User[]
     */
    public function findAll()
    {
        return User::orderBy('first_name')->orderBy('last_name')->get();
    }

    /**
     * @param string $username
     *
     * @return User|null
     */
    public function findOneByUsername($username)
    {
        return User::where('username', '=', $username)->get()->first();
    }

    /**
     * @param string $token
     *
     * @return User|null
     */
    public function findOneByToken($token)
    {
        return User::where('api_key', '=', $token)->get()->first();
    }

    /**
     * @param $id
     * @return User
     * @throws ModelNotFoundException
     */
    public function findById($id)
    {
        return User::findOrFail($id);
    }

    /**
     * @param User $user
     * @return bool
     * @throws ModelValidationException
     */
    public function save(User $user)
    {
        //TODO : add validator
        return $user->save();
    }

    public function saveOrUpdate(User $user)
    {
        try {
            $user->save();
        } catch (\Exception $e) {
            $user->update();
        }
    }

    public function findByStatus($currentUser, $status)
    {
        $users = User::whereNotIn('id', [$currentUser->id])->whereActive($status)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $formatedUsers = [];
        foreach ($users as $user) {
            $formatedUsers[] = array(
                'username' => $user->username,
                'fullname' => $user->present()->fullname,
                'gravatar' => $user->gravatar()
            );
        }
        return $formatedUsers;
    }

    /**
     * Get users with a given role inside the given project.
     *
     * @param  UserRole $role
     * @param  Project  $project
     * @return Collection|User[]
     */
    public function getUserWithRoleInProject(UserRole $role, Project $project)
    {
        return $project->users()->whereUserRoleId($role->id)->get();
    }
}
