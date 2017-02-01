<?php

namespace NeoClocking\Transformers;

use League\Fractal\TransformerAbstract;
use NeoClocking\Models\Project;

class ProjectTransformer extends TransformerAbstract
{
    /**
     * Resources that can be included if requested.
     *
     * @var array
     */
    protected $availableIncludes = [
        'tasks',
    ];

    /**
     * Include resources without needing it to be requested.
     *
     * @var array
     */
    protected $defaultIncludes = [
        'client',
    ];

    /**
     * Transform the project model into the wanted formatted array.
     *
     * @param  Project $project
     * @return array
     */
    public function transform(Project $project)
    {
        return [
            'id'               => $project->id,
            'number'           => $project->number,
            'active'           => $project->active,
            'max_time'         => $project->max_time,
            'remaining_time'   => $project->max_time - $project->present()->calculateLoggedTimeTotal(),
            'allocated_time'   => $project->allocated_time,
            'require_comments' => $project->require_comments,
            'name'             => $project->name,
            'type'             => $project->type,
            'should_not_exceed'=> $project->should_not_exceed
        ];
    }

    /**
     * Include the client related to the project.
     *
     * @param Project $project
     * @return \League\Fractal\Resource\Collection
     */
    public function includeClient(Project $project)
    {
        $client = $project->client;

        return $this->item($client, $this->getClientTransformer(), 'clients');
    }

    /**
     * Include the tasks related to the project.
     *
     * @param Project $project
     * @return \League\Fractal\Resource\Collection
     */
    public function includeTasks(Project $project)
    {
        $tasks = $project->tasks;

        return $this->collection($tasks, $this->getTaskTransformer(), 'tasks');
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
     * Get the task transformer instance.
     *
     * @return TransformerAbstract
     */
    protected function getTaskTransformer()
    {
        return app(TaskTransformer::class);
    }
}
