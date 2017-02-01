<?php

namespace NeoClocking\Policies;

use NeoClocking\Models\Project;
use NeoClocking\Models\User;
use NeoClocking\Models\UserRole;

class ProjectPolicy
{
    protected static $managerAssistantRoles;

    protected static $cache = [];

    /**
     * Validate if the user can manage a given project
     *
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function manage(User $user, Project $project)
    {
        if (!$project->active) {
            return false;
        }

        $cacheKey = $user->id.'::'.$project->id;
        if (!array_key_exists($cacheKey, self::$cache)) {
            $userCanManageProject = $user->projectRoles()
                ->whereProjectId($project->id)
                ->whereIn('user_role_id', $this->getManagerRoles())
                ->count() > 0;

            self::$cache[$cacheKey] = $userCanManageProject || $user->canManageAnyProject();
        }
        return self::$cache[$cacheKey];
    }

    /**
     * Validate has any access to a given project
     *
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function view(User $user, Project $project)
    {
        return $user->projectRoles()->whereProjectId($project->id)->count() > 0
            || $user->canClockAnyProject()
            || $user->canManageAnyProject();
    }

    protected function getManagerRoles()
    {
        if (!isset(self::$managerAssistantRoles)) {
            self::$managerAssistantRoles = manager_assistant_roles()->lists('id');
        }
        return self::$managerAssistantRoles;
    }
}
