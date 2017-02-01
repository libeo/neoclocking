<?php

namespace NeoClocking\Providers;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use NeoClocking\Models\LogEntry;
use NeoClocking\Models\Project;
use NeoClocking\Models\Task;
use NeoClocking\Policies\LogEntryPolicy;
use NeoClocking\Policies\ProjectPolicy;
use NeoClocking\Policies\TaskPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Task::class     => TaskPolicy::class,
        Project::class  => ProjectPolicy::class,
        LogEntry::class => LogEntryPolicy::class,
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate $gate
     * @return void
     */
    public function boot(GateContract $gate)
    {
        $this->registerPolicies($gate);

        //
    }
}
