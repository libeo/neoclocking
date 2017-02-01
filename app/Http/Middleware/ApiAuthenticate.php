<?php

namespace NeoClocking\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use NeoClocking\Repositories\UserRepository;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ApiAuthenticate
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * Create a new filter instance.
     *
     * @param  Guard $auth
     * @param  UserRepository $users
     */
    public function __construct(Guard $auth, UserRepository $users)
    {
        $this->auth = $auth;
        $this->users = $users;
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
        $user = $this->users->findOneByToken($request->header('X-Authorization'));
        if ($user && ($user->originalUser || $user->isActive())) {
            $this->auth->setUser($user);

            return $next($request);
        }

        throw new UnauthorizedHttpException('X-Authorization');
    }
}
