<?php

namespace NeoClocking\Repositories;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use NeoClocking\Exceptions\ModelValidationException;
use NeoClocking\Models\Client;

/**
 * Client's data
 * No create or save here since Data is copied from LDAP
 */
class ClientRepository
{

    /**
     * @return \Illuminate\Database\Eloquent\Collection|Client[]
     */
    public function findAll()
    {
        return Client::all();
    }

    /**
     * @param int $id
     *
     * @return Client|null
     * @throws ModelNotFoundException
     */
    public function findById($id)
    {
        return Client::findOrFail($id);
    }

    /**
     * @param int $number
     *
     * @return Client
     * @throws ModelNotFoundException
     */
    public function findOneByNumber($number)
    {
        return Client::whereNumber($number)->firstOrFail();
    }

    /**
     * @param Client $client
     * @return bool
     * @throws ModelValidationException
     */
    public function save(Client $client)
    {
        //TODO : add validator
        return $client->save();
    }

    public function saveOrUpdate(Client $client)
    {
        try {
            $client->save();
        } catch (\Exception $e) {
            $client->update();
        }
    }
}
