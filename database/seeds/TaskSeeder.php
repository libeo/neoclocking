<?php

use Illuminate\Database\Seeder;
use NeoClocking\Models\ReferenceType;
use NeoClocking\Models\Task;
use NeoClocking\Repositories\ProjectRepository;
use NeoClocking\Repositories\ReferenceTypeRepository;
use NeoClocking\Repositories\ResourceTypeRepository;
use NeoClocking\Repositories\TaskRepository;


class TaskSeeder extends Seeder
{

    public function run()
    {
        $taskRepo = new TaskRepository();

        $projectRepo = new ProjectRepository();

        $referenceTypeRepo = new ReferenceTypeRepository();

        $resourceTypeRepo = new ResourceTypeRepository();

        $task = new Task();
        $task->name = 'Fait quelque chose';
        $task->number = 123321;
        $task->active = true;
        $task->project_id = $projectRepo->findOneByNumberOrFail('P-99999-01')->id;
        $task->resource_type_id = $resourceTypeRepo->findOneByCode('programmation')->id;
        $task->estimation = 60;
        $task->revised_estimation = 0;
        $task->reference_type_id = $referenceTypeRepo->findOneByCode(ReferenceType::CODE_REDMINE)->id;
        $task->reference_number = 54321;
        $task->require_comments = false;
        $taskRepo->saveOrUpdate($task);

        $task = new Task();
        $task->number = 321123;
        $task->name = 'Fait quelque chose d\'autre';
        $task->active = true;
        $task->project_id = $projectRepo->findOneByNumberOrFail('P-99999-01')->id;
        $task->resource_type_id = $resourceTypeRepo->findOneByCode('design')->id;
        $task->estimation = 240;
        $task->revised_estimation = 300;
        $task->reference_type_id = $referenceTypeRepo->findOneByCode(ReferenceType::CODE_REDMINE)->id;
        $task->reference_number = 12345;
        $task->require_comments = false;
        $taskRepo->saveOrUpdate($task);

        $task = new Task();
        $task->number = 1337;
        $task->name = 'Tache en cours';
        $task->active = true;
        $task->project_id = $projectRepo->findOneByNumberOrFail('P-99999-01')->id;
        $task->resource_type_id = $resourceTypeRepo->findOneByCode('programmation')->id;
        $task->estimation = 240;
        $task->revised_estimation = 300;
        $task->reference_type_id = $referenceTypeRepo->findOneByCode(ReferenceType::CODE_REDMINE)->id;
        $task->reference_number = 12345;
        $task->require_comments = false;
        $taskRepo->saveOrUpdate($task);
    }
}
