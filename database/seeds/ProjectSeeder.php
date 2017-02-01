<?php

use Illuminate\Database\Seeder;
use NeoClocking\Models\Project;
use NeoClocking\Repositories\ClientRepository;
use NeoClocking\Repositories\ProjectRepository;

class ProjectSeeder extends Seeder
{

    public function run()
    {
        $clientRepository = new ClientRepository();
        $projectRepo = $this->container->make(ProjectRepository::class);
        // Not using repo since Projects are normally created via LDAP import
        $project = new Project(
            [
                'name' => 'Fake Project for Tests',
                'number' => 'P-99999-01',
                'max_time' => 60,
                'client_id' => $clientRepository->findOneByNumber('123')->id,
                'require_comments' => false,
            ]
        );

        $projectRepo->saveOrUpdate($project);
    }
}
