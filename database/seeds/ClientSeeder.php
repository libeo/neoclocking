<?php

use Illuminate\Database\Seeder;
use NeoClocking\Models\Client;
use NeoClocking\Repositories\ClientRepository;

class ClientSeeder extends Seeder
{

    public function run()
    {
        // Not using repo since Client's are normally created via LDAP import
        $clientRepo = $this->container->make(ClientRepository::class);

        $client = new Client([
            "name"   => 'Libeo Interne',
            "number" => 123,
        ]);

        $clientRepo->saveOrUpdate($client);
    }

}
