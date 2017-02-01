<?php

namespace NeoClocking\Jobs;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Bus\SelfHandling;
use NeoClocking\Models\Client;
use NeoClocking\Models\Project;
use NeoClocking\Models\User;
use NeoClocking\Models\UserProjectRole;
use NeoClocking\Models\UserRole;
use NeoClocking\Services\Ldap\UniqueIdentifier;
use NeoClocking\Services\LibeoDap\Request;
use NeoClocking\Services\LibeoDap\Response;

class SyncProject extends Job implements SelfHandling
{
    use UniqueIdentifier, DispatchesJobs;

    /**
     * The project to synchronize with LibeDap.
     *
     * @var Project
     */
    protected $project;

    /**
     * Create a new job instance.
     *
     * @param Project $project
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * Execute the job.
     *
     * @param Request $request
     */
    public function handle(Request $request)
    {
        $data = $request->execute('/projects/' . $this->project->number);

        $this->updateClient($data->customer);
        $this->updateProject($data);
        $this->updateRole('manager', $data->project_managers ?: []);
        $this->updateRole('assistant', $data->project_assistants ?: []);
        $this->updateRole('member', $data->project_members ?: []);
    }

    /**
     * Update users' roles on project.
     *
     * @param string $role
     * @param array  $members
     */
    protected function updateRole($role, $members)
    {
        $userRole = $this->getRole($role);
        $users = $this->getUsers(collect($members));

        $this->addOrUpdateUsers($users, $userRole);
        $this->cleanUp($users, $userRole);
    }

    /**
     * Update the project with the data from LibeoDap.
     *
     * @param Response $data
     */
    protected function updateProject($data)
    {
        $client = $this->getClient($data->customer);
        $project = $this->project;

        $project->name = $data->name;
        $project->max_time = $data->time_estimate * 60;
        $project->allocated_time = $data->time_allocated * 60;
        $project->active = ($data->status === 'Active');
        $project->type = $data->project_type;
        $project->client_id = $client->id;
        $project->require_comments = ($data->comments_required === 'TRUE');
        $project->save();
    }

    /**
     * Dispatch a job to update the client instance.
     *
     * @param string $customer
     */
    protected function updateClient($customer)
    {
        $client = $this->getClient($customer);

        $this->dispatch(new UpdateClient($client));
    }

    /**
     * Get an instance of client for the given LDAP dn.
     *
     * @param  string $dn
     * @return Client
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    protected function getClient($dn)
    {
        $number = $this->getUid($dn);

        return Client::whereNumber($number)->firstOrNew(compact('number'));
    }

    /**
     * Get an instance of user's role for the given role name.
     *
     * @param  string $role
     * @return UserRole
     */
    protected function getRole($role)
    {
        return UserRole::whereCode($role)->first();
    }

    /**
     * Get the users from the given collection of members' LDAP dn.
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
     * Add or update the project's users' role.
     *
     * @param \Illuminate\Database\Eloquent\Collection|User[] $users
     * @param UserRole $userRole
     */
    protected function addOrUpdateUsers($users, $userRole)
    {
        foreach ($users as $user) {
            UserProjectRole::updateOrCreate(
                [
                    'user_id'    => $user->id,
                    'project_id' => $this->project->id,
                ],
                [
                    'user_role_id' => $userRole->id,
                ]
            );
        }
    }

    /**
     * Delete project's users' role of users not in the provided list.
     *
     * @param \Illuminate\Database\Eloquent\Collection|User[] $users
     * @param UserRole $userRole
     */
    protected function cleanUp($users, $userRole)
    {
        UserProjectRole::whereNotIn('user_id', $users->pluck('id'))
            ->whereProjectId($this->project->id)
            ->whereUserRoleId($userRole->id)
            ->delete();
    }
}
