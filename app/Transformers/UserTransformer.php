<?php

namespace NeoClocking\Transformers;

use League\Fractal\TransformerAbstract;
use NeoClocking\Models\LogEntry;
use NeoClocking\Models\User;

class UserTransformer extends TransformerAbstract
{
    /**
     * Resources that can be included if requested.
     *
     * @var array
     */
    protected $availableIncludes = [
        'log_entries',
        'favourite_tasks',
        'current_log',
    ];

    /**
     * Transform the user model into the wanted formatted array.
     *
     * @param  User $user
     * @return array
     */
    public function transform(User $user)
    {
        return [
            'id'            => $user->id,
            'username'      => $user->username,
            'mail'          => $user->mail,
            'active'        => $user->active,
            'first_name'    => $user->first_name,
            'last_name'     => $user->last_name,
            'week_duration' => $user->week_duration,
            'hourly_cost'   => $user->hourly_cost,
            'fullname'      => $user->present()->fullname,
            'gravatar'      => $user->gravatar()
        ];
    }

    /**
     * Include the log entries related to the user.
     *
     * @param  User $user
     * @return \League\Fractal\Resource\Collection
     */
    public function includeLogEntries(User $user)
    {
        $logEntries = $user->logEntries;

        return $this->collection($logEntries, $this->getLogEntryTransformer(), 'log_entries');
    }

    /**
     * Include the favourite tasks related to the user.
     *
     * @param  User $user
     * @return \League\Fractal\Resource\Collection
     */
    public function includeFavouriteTasks(User $user)
    {
        $tasks = $user->favouriteTasks;

        return $this->collection($tasks, $this->getTaskTransformer(), 'favourite_tasks');
    }

    /**
     * Include the current log related to the user.
     *
     * @param  User $user
     * @return \League\Fractal\Resource\Item
     */
    public function includeCurrentLog(User $user)
    {
        $log = LogEntry::whereUserId($user->id)->whereNull('ended_at')->first();

        if ($log === null) {
            return;
        }

        return $this->item($log, $this->getLogEntryTransformer(), 'current_log');
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
     * Get the task transformer instance.
     *
     * @return TransformerAbstract
     */
    protected function getTaskTransformer()
    {
        return app(TaskTransformer::class);
    }
}
