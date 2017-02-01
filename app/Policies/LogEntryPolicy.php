<?php

namespace NeoClocking\Policies;

use NeoClocking\Models\LogEntry;
use NeoClocking\Models\User;
use NeoClocking\Repositories\LogEntryRepository;
use NeoClocking\Utilities\LogEntryUpdateDatetimeWindow;

class LogEntryPolicy
{
    /**
     * @var LogEntryRepository
     */
    protected $logEntries;

    /**
     * Create a new policy instance.
     *
     * @param LogEntryRepository $logEntries
     */
    public function __construct(LogEntryRepository $logEntries)
    {
        $this->logEntries = $logEntries;
    }

    /**
     * Validate if the user can update the log entry.
     *
     * @param User $user
     * @param LogEntry $logEntry
     * @return bool
     */
    public function update(User $user, LogEntry $logEntry)
    {
        return (
            $logEntry && (
                $logEntry->user_id == $user->id ||
                $user->can('manage', $logEntry->task->project_id)
            ) &&
            ($user->canClockOutsideTimeWindow() || !LogEntryUpdateDatetimeWindow::isOutside($logEntry->started_at))
        );
    }

    /**
     * Validate if the user can delete the log entry.
     *
     * @param User $user
     * @param LogEntry $logEntry
     * @return bool
     */
    public function delete(User $user, LogEntry $logEntry)
    {
        return (
            $logEntry &&
            $logEntry->user_id == $user->id
            && ($user->canClockOutsideTimeWindow() || !LogEntryUpdateDatetimeWindow::isOutside($logEntry->started_at))
        );
    }
}
