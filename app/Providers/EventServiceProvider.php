<?php

namespace NeoClocking\Providers;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use NeoClocking\Exceptions\UserNotAuthorisedException;
use NeoClocking\Models\User;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
    ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher $events
     * @return void
     */
    public function boot(DispatcherContract $events)
    {
        parent::boot($events);

        $events->listen('auth.attempt', function ($credentials, $remember, $login) {
            /** @var User $user */
            $user = User::whereUsername($credentials['username'])->first();
            if ($user && !$user->isActive()) {
                throw new UserNotAuthorisedException;
            }
        });
    }
}
