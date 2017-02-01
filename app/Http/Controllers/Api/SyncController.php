<?php

namespace NeoClocking\Http\Controllers\Api;

use Illuminate\Contracts\Queue\ShouldQueue;
use NeoClocking\Http\Requests;
use NeoClocking\Http\Controllers\Controller;
use NeoClocking\Jobs\SyncClient;
use NeoClocking\Jobs\SyncProject;
use NeoClocking\Jobs\SyncUserPermission;
use NeoClocking\Jobs\SyncUser;
use NeoClocking\Models\Client;
use NeoClocking\Models\Project;
use NeoClocking\Models\User;
use ReflectionClass;

class SyncController extends Controller
{
    public function userPermissions($permission)
    {
        return $this->dispatchSync(SyncUserPermission::class, $permission);
    }

    public function project(Project $project)
    {
        return $this->dispatchSync(SyncProject::class, $project);
    }

    public function user(User $user)
    {
        return $this->dispatchSync(SyncUser::class, $user);
    }

    public function client(Client $client)
    {
        return $this->dispatchSync(SyncClient::class, $client);
    }

    protected function dispatchSync($className, $data)
    {
        $status = $this->getSuccessStatusForJob($className);

        $this->dispatch(new $className($data));

        return ['status' => $status];
    }

    protected function getSuccessStatusForJob($className)
    {
        $reflection = new ReflectionClass($className);

        if ($reflection->implementsInterface(ShouldQueue::class)) {
            return 'queued';
        }

        return 'ok';
    }
}
