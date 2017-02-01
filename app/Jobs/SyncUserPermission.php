<?php

namespace NeoClocking\Jobs;

use Illuminate\Support\Collection;
use NeoClocking\Exceptions\UnsupportedFormatException;
use NeoClocking\Exceptions\UnsupportedPermissionException;
use Illuminate\Contracts\Bus\SelfHandling;
use NeoClocking\Models\User;
use NeoClocking\Models\UserPermission;
use NeoClocking\Services\Ldap\UniqueIdentifier;
use NeoClocking\Services\LibeoDap\Request;

class SyncUserPermission extends Job implements SelfHandling
{
    use UniqueIdentifier;

    /**
     * List of of supported permissions.
     *
     * @var array
     */
    static public $permissions = [
        'nc_control_users' => UserPermission::CONTROL_USERS,
        'nc_clock_outside_time_window' => UserPermission::CLOCK_OUTSIDE_TIME_WINDOW,
        'nc_clock_any_project' => UserPermission::CLOCK_ANY_PROJECT,
        'nc_manage_all_projects' => UserPermission::MANAGE_ANY_PROJECT,
    ];

    /**
     * The permission.
     *
     * @var string
     */
    protected $permission;

    /**
     * Create a new job instance.
     *
     * @param string $permission
     */
    public function __construct($permission)
    {
        $this->permission = $permission;
    }

    /**
     * Execute the job.
     *
     * @param  Request $request
     * @throws UnsupportedPermissionException
     * @throws UnsupportedFormatException
     */
    public function handle(Request $request)
    {
        if (!array_key_exists($this->permission, self::$permissions)) {
            return;
        }

        $data = $request->execute('/groups/'.$this->permission);

        $this->updatePermissions(self::$permissions[$this->permission], $data->group_members);
    }

    /**
     * Update users' permission from the given users list.
     *
     * @param string $permission
     * @param array $members
     */
    protected function updatePermissions($permission, $members)
    {
        $users = $this->getUsers(collect($members));

        $this->addOrUpdateUsersPermission($permission, $users);
        $this->cleanUp($permission, $users);
    }

    /**
     * Get the users instances from the list of LDAP dn.
     *
     * @param  Collection $members
     * @return \Illuminate\Database\Eloquent\Collection|User[]
     */
    protected function getUsers(Collection $members)
    {
        $members->transform(function ($member) {
            return $this->getUid($member);
        });

        return User::whereIn('username', $members)->get();
    }

    /**
     * Add or update users' permission with the given permission.
     *
     * @param string $permission
     * @param \Illuminate\Database\Eloquent\Collection|User[] $users
     */
    protected function addOrUpdateUsersPermission($permission, $users)
    {
        foreach ($users as $user) {
            UserPermission::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'name'    => $permission,
                ]
            );
        }
    }

    /**
     * Delete user's permission for users not present in the given list.
     *
     * @param string $permission
     * @param \Illuminate\Database\Eloquent\Collection|User[] $users
     */
    protected function cleanUp($permission, $users)
    {
        UserPermission::whereNotIn('user_id', $users->pluck('id'))->whereName($permission)->delete();
    }
}
