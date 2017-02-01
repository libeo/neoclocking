<?php

namespace NeoClocking\Transformers;

use League\Fractal\TransformerAbstract;
use NeoClocking\Models\LiveLogEntry;

class LiveLogEntryTransformer extends TransformerAbstract
{
    /**
     * Resources that can be included if requested.
     *
     * @var array
     */
    protected $availableIncludes = [
        'task'
    ];

    /**
     * Transform the log entry model into the wanted formatted array.
     *
     * @param  LiveLogEntry $logEntry
     * @return array
     */
    public function transform(LiveLogEntry $logEntry)
    {
        return [
            'started_at'  => $logEntry->started_at,
            'comment'     => $logEntry->comment
        ];
    }

    /**
     * Include the task related to the log entry.
     *
     * @param  LiveLogEntry $logEntry
     * @return \League\Fractal\Resource\Item
     */
    public function includeTask(LiveLogEntry $logEntry)
    {
        $task = $logEntry->task;

        return $this->item($task, $this->getTaskTransformer(), 'task');
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
