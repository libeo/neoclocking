<?php

namespace NeoClocking\Services;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use NeoClocking\Exceptions\UserNotAuthorisedException;
use NeoClocking\Models\User;
use NeoClocking\Repositories\UserRepository;

/**
 * Class AuthenticationService
 */
class AuthenticationService
{
    /**
     * @var Guard
     */
    protected $auth;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * Inject dependencies
     *
     * @param Guard $auth
     * @param UserRepository $userRepository
     */
    public function __construct(Guard $auth, UserRepository $userRepository)
    {
        $this->auth = $auth;
        $this->userRepository = $userRepository;
    }

    /**
     * Login a user by credentials
     *
     * @param array $credentials
     * @throws UserNotAuthorisedException
     */
    public function login($credentials = array())
    {
        try {
            $loginAttemptSuccessful = $this->auth->attempt($credentials, true);
        } catch (UserNotAuthorisedException $e) {
            throw new UserNotAuthorisedException(trans('neoclocking.auth_service.inactive_user'));
        }

        if (! $loginAttemptSuccessful) {
            throw new UserNotAuthorisedException(trans('neoclocking.auth_service.invalid_credentials'));
        }
    }

    /**
     * Logout a user
     */
    public function logout()
    {
        $originalUser = $this->currentUser()->originalUser;

        $this->auth->logout();
        $this->loginOriginalUser($originalUser);
    }

    /**
     * Login a user, if available
     *
     * @param User|null $user
     */
    private function loginOriginalUser(User $user = null)
    {
        if ($user) {
            $this->auth->login($user);
        }
    }

    /**
     * Login as another user
     *
     * @param string $username
     * @throws UserNotAuthorisedException
     */
    public function loginAs($username)
    {
        $currentUser = $this->currentUser();

        if (!$currentUser->canControlUsers()) {
            throw new UserNotAuthorisedException(trans('neoclocking.auth_service.control_user.invalid_permission'));
        }

        $userToLoginAs = $this->userRepository->findOneByUsername($username);

        if (! $userToLoginAs) {
            throw new ModelNotFoundException(trans('neoclocking.auth_service.control_user.user_not_found'));
        }

        if (!$this->tryToLoginAsSelf($currentUser, $userToLoginAs)) {
            // Need to logout before login
            $this->auth->logout();

            $this->auth->login($userToLoginAs);
            $this->saveOriginalUser($currentUser);
        }

        // If try to login as same user, just do nothing
    }

    /**
     * Return if the current user and the user to login as is the same
     *
     * @param $userToLoginAs
     * @param $currentUser
     * @return bool
     */
    protected function tryToLoginAsSelf($currentUser, $userToLoginAs)
    {
        return $userToLoginAs->id === $currentUser->id;
    }

    /**
     * Save the original user in the current user, if not already set
     *
     * @param User $currentUser
     */
    protected function saveOriginalUser(User $currentUser)
    {
        $originalUser = $this->originalUser();
        if (! $originalUser) {
            $this->currentUser()->originalUser = $currentUser;
        }
    }

    /**
     * Return the currently logged in user
     *
     * @return User
     */
    public function currentUser()
    {
        return $this->auth->user();
    }

    public function originalUser()
    {
        return $this->currentUser()->originalUser;
    }
}
