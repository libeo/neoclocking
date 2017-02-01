<?php

namespace NeoClocking\Services\Updaters;

use App;
use Carbon\Carbon;
use DB;
use NeoClocking\Models\User;
use NeoClocking\Repositories\UserRepository;
use NeoClocking\Services\Ldap\LibeoDataService;
use NeoClocking\Utilities\KeyGenerator;

/**
 * A utility to get all data for as well as create or update a given user
 *
 * Class UserUpdater
 *
 * @package Libeo\NeoClocking\Libraries\Updaters
 */
class UserUpdater extends BaseUpdater
{
    const DEFAULT_STATUS = false;
    const DEFAULT_EMAIL = 'neo-clocking-user-with-no-email@libeo.com';
    const DEFAULT_WEEK_DURATION = 2250; //37.5 hours
    const DEFAULT_COST = 0;

    /**
     * @var User
     */
    protected $model;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var LibeoDataService
     */
    protected $dataService;

    /**
     * Get a user with the given user name or instantiate a new one
     *
     * @param array            $defaultAttributeValues List of attributes and their respective default values
     * @param UserRepository   $userRepository
     * @param LibeoDataService $dataService
     */
    public function __construct(
        array $defaultAttributeValues,
        UserRepository $userRepository,
        LibeoDataService $dataService
    ) {
        parent::__construct($defaultAttributeValues);

        $this->userRepository = $userRepository;
        $this->dataService = $dataService;

        $this->preload();
    }

    protected function preload()
    {
        if (!array_key_exists('id', $this->data) && array_key_exists('username', $this->data)) {
            $data = DB::table('users')->select('*')->where('username', $this->data['username'])->first();

            if ($data !== null) {
                $this->data = (array)$data;
            }
        }
    }

    /**
     * Get data for the given user and update everything
     *
     * @return bool Success
     */
    public function update()
    {
        $username = $this->data['username'];

        $userData = $this->dataService->getUserData($username, false);

        if ($userData->isEmpty()) {
            return false;
        }

        if (!array_key_exists('api_key', $this->data)) {
            $this->data['api_key'] = KeyGenerator::generateRandomKey();
        }

        $status = $userData->get('status', self::DEFAULT_STATUS);

        $this->updateData([
            'first_name' => $userData->get('first_name', $username),
            'last_name'  => $userData->get('last_name', $username),
            'active'     => $status === 'Active',
            'mail'       => $userData->get('mail', self::DEFAULT_EMAIL),
            'hourly_cost'   => self::DEFAULT_COST,
            'week_duration' => self::DEFAULT_WEEK_DURATION,
        ]);

        $workingTimeRaw = $userData->get('workingtime');
        if ($workingTimeRaw !== null && $workingTimeRaw > 0) {
            $this->data['week_duration'] = ((float)$workingTimeRaw) * 60;
        }

        $workDataRaw = $userData->get('worksubcategory');
        if ($workDataRaw !== null) {
            $this->data['hourly_cost'] = $this->dataService->getCostForWorkCategory($workDataRaw);
        }

        if (array_key_exists('id', $this->data)) {
            return DB::table('users')->where('id', $this->data['id'])->update($this->data);
        }

        return DB::table('users')->insert($this->data);
    }

    /**
     * @return bool
     */
    public function createWithDefaults()
    {
        $this->updateData([
            'first_name'    => $this->data['username'],
            'last_name'     => $this->data['username'],
            'active'        => self::DEFAULT_STATUS,
            'week_duration' => self::DEFAULT_WEEK_DURATION,
            'hourly_cost'   => self::DEFAULT_COST,
            'mail'          => self::DEFAULT_EMAIL,
            'api_key'       => KeyGenerator::generateRandomKey(),
        ]);

        if (array_key_exists('id', $this->data)) {
            return DB::table('users')->where('id', $this->data['id'])->update($this->data);
        }

        return DB::table('users')->insert($this->data);
    }
}
