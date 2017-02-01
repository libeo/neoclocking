<?php

namespace NeoClocking\Jobs;

use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Support\Collection;
use NeoClocking\Models\User;
use NeoClocking\Models\UserPermission;
use NeoClocking\Services\Ldap\UniqueIdentifier;
use NeoClocking\Services\LibeoDap\Request;
use NeoClocking\Services\LibeoDap\Response;

class UpdateUserPermissions extends Job implements SelfHandling
{
    use UniqueIdentifier;

    /**
     * @var User
     */
    protected $user;

    /**
     * Create a new job instance.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @param Request $request
     */
    public function handle(Request $request)
    {
        $groups = $request->execute('/groups');

        $inGroups = $this->updatePermissions($groups);
        $this->cleanup($inGroups);
    }

    /**
     * Update user's permissions with data from LibeoDap.
     *
     * @param  Response $groups
     * @return void
     */
    protected function updatePermissions(Response $groups)
    {
        $inGroups = [];

        foreach ($groups->toArray() as $group) {
            if (!$this->supportsGroup($group) || !$this->groupContainsUser($group)) {
                continue;
            }

            $inGroups[] = SyncUserPermission::$permissions[$group['name']];

            UserPermission::updateOrCreate(
                [
                    'user_id' => $this->user->id,
                    'name'    => SyncUserPermission::$permissions[$group['name']],
                ]
            );
        }

        return $inGroups;
    }

    /**
     * Delete user's permission not the given groups.
     *
     * @param  array $groups
     * @return void
     */
    protected function cleanup($groups)
    {
        UserPermission::whereUserId($this->user->id)->whereNotIn('name', $groups)->delete();
    }

    /**
     * Validate the group contains the user.
     *
     * @param  array $group
     * @return bool
     */
    protected function groupContainsUser($group)
    {
        $username = $this->user->username;

        return array_first($group['group_members'], function ($i, $dn) use ($username) {
            return $username === $this->getUid($dn);
        }) !== null;
    }

    /**
     * Validate the support for the users' group.
     *
     * @param  array $group
     * @return bool
     */
    protected function supportsGroup($group)
    {
        return array_key_exists($group['name'], SyncUserPermission::$permissions);
    }
}
