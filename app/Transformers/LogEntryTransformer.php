<?php

namespace NeoClocking\Transformers;

use League\Fractal\TransformerAbstract;
use NeoClocking\Models\LogEntry;

class LogEntryTransformer extends TransformerAbstract
{
    /**
     * Resources that can be included if requested.
     *
     * @var array
     */
    protected $availableIncludes = [
        'task',
        'user',
        'project',
        'client',
    ];

    /**
     * Transform the log entry model into the wanted formatted array.
     *
     * @param  LogEntry $logEntry
     * @return array
     */
    public function transform(LogEntry $logEntry)
    {
        return [
            'id'            => $logEntry->id,
            'started_at'    => (string) $logEntry->started_at,
            'ended_at'      => (string) $logEntry->ended_at,
            'validated'     => $logEntry->validated,
            'hourly_cost'   => $logEntry->hourly_cost,
            'comment'       => $logEntry->comment,
            'duration'      => $logEntry->ended_at->diffInMinutes($logEntry->started_at),
            //'overlaps'      => $logEntry->overlapsAnother(),
            'can_be_deleted' => user()->can('delete', $logEntry),
            'can_be_edited' => user()->can('update', $logEntry)
        ];
    }

    /**
     * Include the user related to the log entry.
     *
     * @param  LogEntry $logEntry
     * @return \League\Fractal\Resource\Item
     */
    public function includeUser(LogEntry $logEntry)
    {
        $user = $logEntry->user;

        return $this->item($user, $this->getUserTransformer(), 'user');
    }

    /**
     * Include the task related to the log entry.
     *
     * @param  LogEntry $logEntry
     * @return \League\Fractal\Resource\Item
     */
    public function includeTask(LogEntry $logEntry)
    {
        $task = $logEntry->task;

        return $this->item($task, $this->getTaskTransformer(), 'task');
    }

    /**
     * Include the project related to the log entry's task.
     *
     * @param  LogEntry $logEntry
     * @return \League\Fractal\Resource\Item
     */
    public function includeProject(LogEntry $logEntry)
    {
        $project = $logEntry->task->project;

        return $this->item($project, $this->getProjectTransformer(), 'project');
    }

    /**
     * Include the project related to the log entry's task.
     *
     * @param  LogEntry $logEntry
     * @return \League\Fractal\Resource\Item
     */
    public function includeClient(LogEntry $logEntry)
    {
        $client = $logEntry->task->project->client;

        return $this->item($client, $this->getClientTransformer(), 'client');
    }

    /**
     * Get the user transformer instance.
     *
     * @return TransformerAbstract
     */
    protected function getUserTransformer()
    {
        return app(UserTransformer::class);
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
     * Get the client transformer instance.
     *
     * @return TransformerAbstract
     */
    protected function getClientTransformer()
    {
        return app(ClientTransformer::class);
    }
}
