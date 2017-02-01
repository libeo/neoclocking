<?php

use Illuminate\Database\Seeder;
use NeoClocking\Models\Client;
use NeoClocking\Models\Project;
use NeoClocking\Models\ReferenceType;
use NeoClocking\Models\ResourceType;
use NeoClocking\Models\Task;
use NeoClocking\Models\UserProjectRole;

class SearchSeeder extends Seeder
{
    public function run()
    {
        $user = factory(NeoClocking\Models\User::class)->create();

        $client1 = Client::firstOrCreate([
            "name"   => 'Walmart',
            "number" => random_int(1, 500),
        ]);

        $client2 = Client::firstOrCreate([
            "name"   => 'Le village des sports',
            "number" => random_int(1, 500),
        ]);

        $projet1 = Project::firstOrNew([
            'name' => 'Refonte du magasin en ligne',
            'number' => 'P-341444-01',
        ]);
        $projet1->max_time = 60;
        $projet1->client_id = $client1->id;
        $projet1->require_comments = true;
        $projet1->save();

        UserProjectRole::firstOrCreate([
            'user_id' => $user->id,
            'project_id' => $projet1->id,
            'user_role_id' => 1,
        ]);

        $projet2 = Project::firstOrCreate([
            'name' => 'Construction de super slides',
            'number' => 'P-12333-01',
            'max_time' => 60,
            'client_id' => $client2->id,
            'require_comments' => false,
        ]);

        UserProjectRole::firstOrCreate([
            'user_id' => $user->id,
            'project_id' => $projet2->id,
            'user_role_id' => 1,
        ]);

        $task1 = Task::firstOrCreate([
            'name' => 'Ajout de produit dans magasin en ligne Walmart',
            'number' => 123321,
            'active' => true,
            'project_id' => $projet1->id,
            'resource_type_id' => ResourceType::first()->id,
            'estimation' => 60,
            'revised_estimation' => 0,
            'reference_type_id' => ReferenceType::first()->id,
            'reference_number' => 54321,
        ]);

        $task2 = Task::firstOrCreate([
            'name' => 'Refactor le tout en Stylus',
            'number' => 93249,
            'active' => true,
            'project_id' => $projet1->id,
            'resource_type_id' => ResourceType::first()->id,
            'estimation' => 60,
            'revised_estimation' => 0,
            'reference_type_id' => ReferenceType::first()->id,
            'reference_number' => 12356,
        ]);

        $task3 = Task::firstOrCreate([
            'name' => 'Évaluer comment ca marche',
            'number' => 4444444,
            'active' => true,
            'project_id' => $projet2->id,
            'resource_type_id' => ResourceType::first()->id,
            'estimation' => 60,
            'revised_estimation' => 0,
            'reference_type_id' => ReferenceType::first()->id,
            'reference_number' => 12356,
        ]);
    }
}
