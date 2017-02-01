<?php
namespace NeoClocking\Presenters;

use NeoClocking\Models\LogEntry;

class LogEntryPresenter extends BasePresenter
{
    /** @var LogEntry */
    protected $entity;

    /**
     * Calculate duration of log entry and format as Hours:Minutes
     *
     * @return string
     */
    public function duration()
    {
        $end = $this->entity->ended_at;

        if (empty($end)) {
            return '0:00';
        }

        $start = $this->entity->started_at;
        $diff = $end->diffInMinutes($start);

        return $this->minutesToHours($diff);
    }
}
