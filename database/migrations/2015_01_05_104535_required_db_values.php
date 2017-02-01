<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use NeoClocking\Models\ReferenceType;
use NeoClocking\Models\ResourceType;

class RequiredDbValues extends Migration
{

    static private $resourcesTypes = [
        ResourceType::CODE_OTHER => 'Autre/Legacy',
        'formation'              => 'Formation',
        'strategie'              => 'Stratégie',
        'wireframes'             => 'Wireframes',
        'design'                 => 'Design',
        'integration'            => 'Intégration',
        'programmation'          => 'Programmation',
        'sysadmin'               => 'Sysadmin',
        'gestion_de_projet'      => 'Gestion de projet',
        'qa'                     => 'Assurance qualité',
        'direction_artistique'   => 'Direction Artistique',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // TODO move in the seeder
        // REFERENCE_TYPE
        // This type of data won't be created from GUI at runtime
        Model::unguard();
        ReferenceType::create(
            [
                'code'   => ReferenceType::CODE_REDMINE,
                'name'   => 'Redmine',
                'prefix' => 'https://projets.libeo.com/issues/',
            ]
        );


        // RESOURCES_TYPE
        array_walk(self::$resourcesTypes, function ($name, $code) {
            ResourceType::create([
                'code' => $code,
                'name' => $name,
            ]);
        });


        // STATUSES
        DB::statement(
            "INSERT INTO statuses (id, code, name)
             VALUES (1, 'actif', 'Actif'), (2, 'ferme', 'Fermé')"
        );

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }

}
