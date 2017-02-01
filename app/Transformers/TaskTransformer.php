<?php

namespace NeoClocking\Transformers;

use League\Fractal\TransformerAbstract;
use NeoClocking\Models\Task;

class TaskTransformer extends TransformerAbstract
{
    /**
     * Resources that can be included if requested.
     *
     * @var array
     */
    protected $availableIncludes = [
        'project',
        'milestone',
        'log_entries',
        'client',
    ];

    /**
     * Include resources without needing it to be requested.
     *
     * @var array
     */
    protected $defaultIncludes = [
        'resource',
        'reference',
    ];

    /**
     * Transform the task model into the wanted formatted array.
     *
     * @param  Task $task
     * @return array
     */
    public function transform(Task $task)
    {
        return [
            'id'                  => $task->id,
            'number'              => $task->number,
            'name'                => $task->name,
            'estimation'          => $task->estimation,
            'reference_number'    => $task->reference_number,
            'revised_estimation'  => $task->revised_estimation,
            'logged_time'         => $task->logged_time,
            'estimation_exceeded' => $task->hasExceededEstimation(),
            'active'              => $task->active,
            'favourited'          => user()->hasFavourited($task),
            'user_can_edit'       => user()->can('update', $task),
            'require_comments'    => $task->require_comments,
            'can_clock'           => $task->can_clock,
        ];
    }

    /**
     * Include the client related to the project of the task.
     *
     * @param  Task $task
     * @return \League\Fractal\Resource\Item
     */
    public function includeClient(Task $task)
    {
        $client = $task->project->client;

        return $this->item($client, $this->getClientTransformer(), 'client');
    }

    /**
     * Include the project related to the task.
     *
     * @param  Task $task
     * @return \League\Fractal\Resource\Item
     */
    public function includeProject(Task $task)
    {
        $project = $task->project;

        return $this->item($project, $this->getProjectTransformer(), 'project');
    }

    /**
     * Include the log entries related to the task.
     *
     * @param  Task $task
     * @return \League\Fractal\Resource\Collection
     */
    public function includeLogEntries(Task $task)
    {
        $logs = $task->logEntries;

        return $this->collection($logs, $this->getLogEntryTransformer(), 'log_entries');
    }

    /**
     * Include the resource type related to the task.
     *
     * @param  Task $task
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includeResource(Task $task)
    {
        $resource = $task->resourceType;

        if ($resource === null) {
            return;
        }

        return $this->item($resource, $this->getResourceTransformer(), 'resource');
    }

    /**
     * Include the reference type related to the task.
     *
     * @param  Task $task
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includeReference(Task $task)
    {
        $reference = $task->referenceType;

        if ($reference === null) {
            return;
        }

        return $this->item($reference, $this->getReferenceTransformer(), 'reference');
    }

    /**
     * Include the reference type related to the task.
     *
     * @param  Task $task
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     */
    public function includeMilestone(Task $task)
    {
        $milestone = $task->milestone;

        if ($milestone === null) {
            return;
        }

        return $this->item($milestone, $this->getMilestoneTransformer(), 'milestone');
    }

    /**
     * Get the client transformer instance.
     *
     * @return TransformerAbstract
     */
    protected function getClientTransformer()
    {
        return app(ClientTransformer::class);
    }


    /**
     * Get the resource type transformer instance.
     *
     * @return TransformerAbstract
     */
    protected function getResourceTransformer()
    {
        return app(ResourceTypeTransformer::class);
    }

    /**
     * Get the reference type transformer instance.
     *
     * @return TransformerAbstract
     */
    protected function getReferenceTransformer()
    {
        return app(ReferenceTypeTransformer::class);
    }

    /**
     * Get the log entry transformer instance.
     *
     * @return TransformerAbstract
     */
    protected function getLogEntryTransformer()
    {
        return app(LogEntryTransformer::class);
    }

    /**
     * Get the project transformer instance.
     *
     * @return TransformerAbstract
     */
    protected function getProjectTransformer()
    {
        return app(ProjectTransformer::class);
    }

    /**
     * Get the milestone transformer instance.
     *
     * @return TransformerAbstract
     */
    protected function getMilestoneTransformer()
    {
        return app(MilestoneTransformer::class);
    }
}
