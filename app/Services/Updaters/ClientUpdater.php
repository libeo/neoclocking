<?php

namespace NeoClocking\Services\Updaters;

use App;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use NeoClocking\Models\Client;
use NeoClocking\Models\GenericData;
use NeoClocking\Repositories\ClientRepository;
use NeoClocking\Services\Ldap\LibeoDataService;

/**
 * A utility to get all data for as well as create or update a given user
 *
 * Class ClientUpdater
 *
 * @package Libeo\NeoClocking\Libraries\Updaters
 */
class ClientUpdater extends BaseUpdater
{

    /**
     * @var Client
     */
    protected $model;

    /**
     * @var ClientRepository
     */
    private $clientRepo;

    /**
     * Get a user with the given user name or instantiate a new one
     *
     * @param Integer $clientNumber
     * @param Array $defaultAttributeValues
     */
    public function __construct($clientNumber, $defaultAttributeValues = [])
    {
        parent::__construct($defaultAttributeValues);

        $this->clientRepo = app(ClientRepository::class);
        $this->data['number'] = $clientNumber;

        try {
            $this->model = $this->clientRepo->findOneByNumber($clientNumber);
        } catch (ModelNotFoundException $e) {
            $this->model = new Client();
        }
    }

    /**
     * Get data for the given customer and update everything
     *
     * @return bool Success
     */
    public function update()
    {
        /** @var GenericData $clientData */
        $clientData = app(LibeoDataService::class)->getClientData($this->data['number']);

        if (!$clientData->hasAttribute('name')) {
            return false;
        }

        $clientName = $clientData->get('name');

        $this->data['name'] = $clientName;

        $this->forceUpdate();
        return $this->clientRepo->save($this->model);
    }

    /**
     * Create or update client with specified data
     *
     * @param $name
     * @param $number
     * @param $id
     * @param $createdAt
     *
     * @return bool
     */
    public function manualUpdate($name, $number, $id, $createdAt)
    {
        $this->data['name'] = $name;
        $this->data['number'] = $number;
        $this->data['id'] = $id;
        $this->data['created_at'] = $createdAt;

        $this->forceUpdate();

        return $this->clientRepo->save($this->model);
    }
}
