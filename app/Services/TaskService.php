<?php

namespace NeoClocking\Services;

use Illuminate\Support\MessageBag;
use NeoClocking\Exceptions\ModelOperationDeniedException;
use NeoClocking\Exceptions\ModelValidationException;
use NeoClocking\Models\Project;
use NeoClocking\Models\Task;
use NeoClocking\Repositories\ProjectRepository;
use NeoClocking\Repositories\ReferenceTypeRepository;
use NeoClocking\Repositories\ResourceTypeRepository;
use NeoClocking\Repositories\TaskRepository;
use NeoClocking\Utilities\TimeFormater;
use SearchIndex;
use Validator;
use Lang;

class TaskService
{
    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    /**
     * @var ResourceTypeRepository
     */
    private $resourceTypeRepository;

    /**
     * @var ReferenceTypeRepository
     */
    private $referenceTypeRepository;

    public function __construct(
        TaskRepository $taskRepository,
        ProjectRepository $projectRepository,
        ResourceTypeRepository $resourceTypeRepository,
        ReferenceTypeRepository $referenceTypeRepository
    ) {
        $this->taskRepository = $taskRepository;
        $this->projectRepository = $projectRepository;
        $this->resourceTypeRepository = $resourceTypeRepository;
        $this->referenceTypeRepository = $referenceTypeRepository;
    }

    /**
     * @param int $taskId
     * @param string $name
     * @param boolean $active
     * @param int $estimation
     * @param int $revisedEstimation
     * @param int $resourceTypeId
     * @param int $refTypeId
     * @param int $refNumber
     * @param bool $requireComments
     * @param int $projectId
     * @return Task|null
     */
    public function createOrUpdateTask(
        $taskId,
        $name,
        $active,
        $estimation,
        $revisedEstimation,
        $resourceTypeId,
        $refTypeId,
        $refNumber,
        $requireComments,
        $projectId
    ) {
        if (empty($taskId)) {
            $task = new Task();
        } else {
            $task = $this->taskRepository->findById($taskId);
        }

        $task->name = $name;
        $task->estimation = $estimation;
        $task->revised_estimation = $revisedEstimation;
        $task->require_comments = $requireComments;
        $task->project_id = $projectId;
        $task->resource_type_id = $resourceTypeId;
        $task->active = $active;
        $task->reference_type_id = $refTypeId;
        $task->reference_number = $refNumber;
        $this->taskRepository->save($task);

        SearchIndex::upsertToIndex($task);

        return $this->taskRepository->findById($task->id);
    }

    /**
     * @param array $tasks
     * @param Project $project
     * @return array
     */
    public function formatTasks(Project $project, $tasks = array())
    {
        foreach ($tasks as $id => $task) {
            if (empty($task['project_id'])) {
                $task['project_id'] = $project->id;
            }
            $task['milestone_id'] = empty($task['milestone_id']) ? null : $task['milestone_id'];

            $formatedRevisedEstimation = TimeFormater::formattedTimeToMinutes($task['revised_estimation']);
            $task['revised_estimation'] = empty($task['revised_estimation']) ? null : $formatedRevisedEstimation;

            if (strpos($id, 'new_') === 0) {
                $task['estimation'] = $task['revised_estimation'];
                unset($task['revised_estimation']);
            }

            $tasks[$id] = $task;
        }
        return $tasks;
    }

    /**
     * @param array $tasks
     * throw ModelOperationDeniedException
     */
    public function saveTasks($tasks = array())
    {
        $this->validateTasks($tasks);
        foreach ($tasks as $taskId => $taskData) {
            $task = null;
            if (strpos($taskId, 'new_') === false) {
                $task = Task::whereId($taskId)->first();
            } else {
                $task = new Task();
            }
            $task->fill($taskData);

            $saved = $task->save();
            if (!$saved) {
                throw new ModelOperationDeniedException("Failed to save task $taskId");
            }

            $task->logEntries()->touch();

            SearchIndex::upsertToIndex($task->fresh());
        }
    }

    /**
     * @param array $tasks
     * @throw ModelValidationException
     */
    public function validateTasks($tasks = array())
    {
        $rules = [
            'name'               => 'required',
            'estimation'         => 'numeric',
            'revised_estimation' => 'numeric',
            'active'             => 'boolean',
            'resource_type_id'   => 'required|exists:resource_types,id',
            'require_comments'   => 'boolean',
            'milestone_id'       => 'exists:milestones,id',
            'project_id'         => 'required|exists:projects,id',
        ];

        $errors = [];
        foreach ($tasks as $id => $task) {
            $validator = Validator::make($task, $rules);
            $validator->setAttributeNames(array(
                'name' => Lang::get('neoclocking.task.name'),
                'resource_type_id' => Lang::get('neoclocking.task.resource_type_id')
            ));

            $validatorErrors = $validator->errors();
            if ($validatorErrors->any()) {
                $errors[$id] = $validatorErrors->getMessages();
            }
        }

        if (count($errors) > 0) {
            $exception = new ModelValidationException();
            $exception->setValidationErrors(new MessageBag($errors));
            throw $exception;
        }
    }
}
