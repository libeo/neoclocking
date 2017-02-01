<?php

namespace NeoClocking\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use NeoClocking\Models\User;
use NeoClocking\Models\UserPermission;

class LibeoDapAuthenticate
{
    /**
     * @var Guard
     */
    protected $auth;

    /**
     * Create a middleware instance.
     *
     * @param Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = $this->getUser();

        if (!$user || !$user->permissions()->whereName(UserPermission::LIBEO_DAP_SYNC)->exists()) {
            abort(401);
        }

        return $next($request);
    }

    /**
     * Get an instance of the connected user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null|User
     */
    protected function getUser()
    {
        return $this->auth->user();
    }
}
