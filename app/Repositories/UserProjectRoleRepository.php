<?php

namespace NeoClocking\Repositories;

use DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use NeoClocking;
use NeoClocking\Exceptions\ModelValidationException;
use NeoClocking\Models\Project;
use NeoClocking\Models\User;
use NeoClocking\Models\UserProjectRole;
use NeoClocking\Models\UserRole;

/**
 * Class UserProjectRoleRepository
 * @package Repositories
 *
 * TODO: plusieurs méthodes ici devraient faire partie d'un "UserRoleService"
 */
class UserProjectRoleRepository
{

    /**
     * @return UserProjectRole[] List of project
     */
    public function findAll()
    {
        return UserProjectRole::all();
    }

    /**
     * @param Integer $id
     * @return UserProjectRole
     * @throws ModelNotFoundException
     */
    public function findById($id)
    {
        return UserProjectRole::findOrFail($id);
    }

    /**
     * @param Project $project
     * @return UserProjectRole
     */
    public function findByProject(Project $project)
    {
        return UserProjectRole::query()->where('project_id', $project->id)->get();
    }

    /**
     * @param User $user
     * @return UserProjectRole
     */
    public function findByUser(User $user)
    {
        return UserProjectRole::query()->where('user_id', $user->id)->get();
    }

    /**
     * @param UserRole $role
     * @return UserProjectRole
     */
    public function findByRole(UserRole $role)
    {
        return UserProjectRole::query()->where('user_role_id', $role->id)->get();
    }

    /**
     * @param User $user
     * @param Project $project
     * @return UserRole|null
     * TODO : transférer dans service
     */
    public function getRoleOfUserInProject(User $user, Project $project)
    {
        $role = DB::table('user_roles')
            ->select('user_roles.id', 'user_roles.code')
            ->join('user_project_roles', 'user_roles.id', '=', 'user_project_roles.user_role_id')
            ->where('user_id', '=', $user->id)
            ->where('project_id', '=', $project->id)
            ->orderBy('priority', 'desc')
            ->limit(1)
            ->first();

        if (!empty($role)) {
            return app(UserRoleRepository::class)->findOneByCode($role->code);
        }
        return null;
    }

    /**
     * @param User $user
     * @param UserRole $role
     * @return array
     * TODO : transférer dans service
     */
    public function findProjectsWhereUserHasRole(User $user, UserRole $role)
    {
        $projects = DB::table('projects')
            ->join('user_project_roles', 'projects.id', '=', 'user_project_roles.project_id')
            ->where('user_id', '=', $user->id)
            ->where('user_role_id', '=', $role->id)
            ->select('projects.id', 'projects.name', 'projects.number')
            ->get();
        return $projects;
    }

    /**
     * @param User $user
     * @return array
     * TODO : transférer dans service
     */
    public function findProjectsForUser(User $user)
    {
        $projects = DB::table('projects')
            ->join('user_project_roles', 'projects.id', '=', 'user_project_roles.project_id')
            ->where('user_id', '=', $user->id)
            ->select('projects.id', 'projects.name', 'projects.number', 'user_role_id')
            ->get();
        return $projects;
    }

    /**
     * @param UserProjectRole $userProjectRole
     * @return bool updated
     * @throws ModelValidationException
     */
    public function save(UserProjectRole $userProjectRole)
    {
        return $userProjectRole->save();
    }

    /**
     * @param UserProjectRole $userProjectRole
     * @return bool deleted
     */
    public function delete(UserProjectRole $userProjectRole)
    {
        return $userProjectRole->delete();
    }

    /**
     * @param Project $project
     * @return UserProjectRole[] deleted UserProjectRoles
     */
    public function deleteByProject(Project $project)
    {
        $userProjectRoles = UserProjectRole::query()->where('project_id', $project->id)->get();
        return $this->deleteMany($userProjectRoles);
    }

    /**
     * @param User $user
     * @return UserProjectRole[] deleted UserProjectRoles
     */
    public function deleteByUser(User $user)
    {
        $userProjectRoles = UserProjectRole::query()->where('user_id', $user->id)->get();
        return $this->deleteMany($userProjectRoles);
    }

    /**
     * @param UserProjectRole[] $userProjectRoles
     * @return UserProjectRole[] deleted UserProjectRoles
     */
    private function deleteMany($userProjectRoles)
    {
        foreach ($userProjectRoles as $userProjectRole) {
            $this->delete($userProjectRole);
        }
        return $userProjectRoles;
    }
}
