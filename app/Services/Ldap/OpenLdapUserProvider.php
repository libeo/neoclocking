<?php

namespace NeoClocking\Services\Ldap;

use Exception;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\Connection;
use NeoClocking\Repositories\UserRepository;

/**
 * An OpenLDAP authentication driver for Laravel 4.
 *
 * @author Yuri Moens (yuri.moens@gmail.com)
 */
class OpenLdapUserProvider implements UserProvider
{
    /**
     * The Eloquent user model.
     *
     * @var string
     */
    protected $model;

    /**
     * The active database connection.
     *
     * @param \Illuminate\Database\Connection
     */
    protected $dbConn;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * Create a new LDAP user provider.
     *
     * @param Connection $dbConn
     *
     * @throws Exception
     */
    public function __construct(Connection $dbConn)
    {
        $this->dbConn = $dbConn;
        $this->userRepository = app(UserRepository::class);
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param mixed $identifier
     *
     * @return \Illuminate\Auth\UserInterface|null
     */
    public function retrieveById($identifier)
    {
        return $this->userRepository->findById($identifier);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param mixed $identifier
     * @param string $token
     *
     * @return \Illuminate\Auth\UserInterface|null
     */
    public function retrieveByToken($identifier, $token)
    {
        $user = $this->userRepository->findOneByUsername($identifier);
        if (!empty($user)) {
            $model = $this->createModel();

            return $model->newQuery()
                ->where('id', $user->id)
                ->where($model->getRememberTokenName(), $token)
                ->first();
        }
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param Authenticatable $user
     * @param string $token
     *
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        if (!$user instanceof GenericUser) {
            $user->setAttribute($user->getRememberTokenName(), $token);
            $user->save();
        }
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param array $credentials
     *
     * @return Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        $user = $this->userRepository->findOneByUsername($credentials['username']);

        if (!isset($user)) {
            return null;
        }

        return $user;
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param Authenticatable $user
     * @param array
     *
     * @return boolean
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        if ($user == null || empty($credentials['password'])) {
            return false;
        }

        return $this->attemptLogin(
            $credentials['username'],
            $credentials['password'],
            config('ldap.login_attribute'),
            config('ldap.basedn')
        );
    }

    /**
     * Validate login credentials by attempting an LDAP bind with the given credentials.
     *
     * @param string $username
     * @param string $password
     * @param string $idField
     * @param string $groupName
     *
     * @return bool
     */
    public function attemptLogin($username, $password, $idField = "uid", $groupName = "people")
    {
        $connection = ldap_connect(
            config('ldap.protocol') . '://' . config('ldap.host'),
            config('ldap.port')
        );
        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, config('ldap.version'));
        try {
            $status = ldap_bind(
                $connection,
                "{$idField}={$username},{$groupName}",
                $password
            );
            ldap_unbind($connection);
            return $status;
        } catch (\Exception $e) {
            return false;
        }
    }
}
