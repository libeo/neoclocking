<?php

namespace NeoClocking\Services\Updaters;

use App;
use NeoClocking\Models\User;
use NeoClocking\Models\UserProjectRole;
use NeoClocking\Models\UserRole;
use NeoClocking\Repositories\ProjectRepository;
use NeoClocking\Repositories\UserProjectRoleRepository;
use NeoClocking\Repositories\UserRepository;
use NeoClocking\Repositories\UserRoleRepository;
use NeoClocking\Services\Ldap\LibeoDataService;

/**
 * A utility to get all needed data and create or update
 * The relationships between a given user its projects or project its users.
 *
 * Class UserProjectRolesUpdater
 *
 * @package NeoClocking\Services\Updaters
 */
class UserProjectRolesUpdater
{

    /**
     * @var UserProjectRoleRepository
     */
    private $userProjectRoleRepo;

    /**
     * @var array
     */
    private static $roleCache;


    public function __construct()
    {
        if (static::$roleCache === null) {
            $userRoleRepo = app(UserRoleRepository::class);
            static::$roleCache = [
                'manager'          => $userRoleRepo->findOneByCode(UserRole::CODE_MANAGER),
                'managerassistant' => $userRoleRepo->findOneByCode(UserRole::CODE_ASSISTANT),
                'member'           => $userRoleRepo->findOneByCode(UserRole::CODE_MEMBER),
            ];
        }

        $this->userProjectRoleRepo = app(UserProjectRoleRepository::class);
    }

    /**
     * Delete and recreate all User/Project/Role relationships
     * For users in a given Project
     *
     * @param String $projectNumber
     *
     * @return Boolean
     */
    public function updateByProject($projectNumber)
    {
        $project = app(ProjectRepository::class)->findOneByNumberOrFail($projectNumber);
        if (!isset($project)) {
            return false;
        }
        $this->userProjectRoleRepo->deleteByProject($project);
        return $this->updateRolesForProject($project);
    }

    /**
     * Delete and recreate all User/Project/Role relationships
     * For a given User
     *
     * @param String $username
     *
     * @return Boolean
     */
    public function updateByUser($username)
    {
        $user = app(UserRepository::class)->findOneByUsername($username);

        if (empty($user)) {
            return false;
        }
        $this->userProjectRoleRepo->deleteByUser($user);

        $success = true;
        $projects = app(LibeoDataService::class)->getProjectsForUser($username);
        foreach ($projects as $projectNumber) {
            $project = app(ProjectRepository::class)->findOneByNumberOrFail($projectNumber);
            $projectSuccess = $this->updateRolesForProject($project, $username);
            if (!$projectSuccess) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * @param \NeoClocking\Models\Project $project
     * @param string|null $username Only create for this user
     *
     * @return bool
     */
    private function updateRolesForProject($project, $username = null)
    {
        if (empty($project)) {
            return false;
        }

        $success = true;
        $rolesData = app(LibeoDataService::class)->getRolesForProject($project->number);
        /** @var UserRepository $userRepo */
        $userRepo = app(UserRepository::class);

        foreach ($rolesData as $roleIndex => $roles) {
            foreach ($roles as $roleUsername) {
                if (is_null($username) || $username == $roleUsername) {
                    $user = $userRepo->findOneByUsername($roleUsername);
                    if (empty($user)) {
                        $user = $this->tryToImportUser($roleUsername);
                    }
                    if (!empty($user)) {
                        $userProjectRole = new UserProjectRole();
                        $userProjectRole->user_id = $user->id;
                        $userProjectRole->project_id = $project->id;
                        $userProjectRole->user_role_id = static::$roleCache[$roleIndex]->id;

                        $saved = $this->userProjectRoleRepo->save($userProjectRole);
                        if (!$saved) {
                            $success = false;
                        }
                    } else {
                        $success = false;
                    }
                }
            }
        }
        return $success;
    }

    /**
     * If the user does not currently exist try to import it
     *
     * @param $username
     *
     * @return User|null
     */
    private function tryToImportUser($username)
    {
        $userUpdater = app(UserUpdater::class, [['username' => $username]]);
        $success = $userUpdater->update();
        if (!$success) {
            return null;
        }
        return app(UserRepository::class)->findOneByUsername($username);
    }
}
