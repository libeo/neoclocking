<?php
namespace NeoClocking\Presenters;

use DB;
use NeoClocking\Models\LogEntry;
use NeoClocking\Models\Project;

class ProjectPresenter extends BasePresenter
{
    /** @var Project */
    protected $entity;

    /**
     * Get project number and name seperated by a dash
     * Ex. P-01-1 - Libéo Interne
     *
     * @return string
     */
    public function numberAndName()
    {
        return $this->entity->number . " - " . $this->entity->name;
    }

    /**
     * Max budgeted time for this project
     *
     * @return string
     */
    public function totalTime()
    {
        return $this->minutesToHours($this->entity->max_time);
    }

    /**
     * The sum of all estimated time for tasks in this project
     *
     * @return string
     */
    public function allottedTime()
    {
        return $this->minutesToHours($this->calculateAllottedTime());
    }

    /**
     * Total of all logged hours in this project
     *
     * @return string
     */
    public function loggedTime()
    {
        return $this->minutesToHours($this->calculateLoggedTimeTotal());
    }

    /**
     * Total Time - Used Time
     * @return string
     */
    public function remainingTime()
    {
        $remainingTime = $this->entity->max_time - $this->calculateLoggedTimeTotal();
        return $this->minutesToHours($remainingTime);
    }

    /**
     * The sum of all estimated time for tasks in this project
     *
     * @return int
     */
    public function calculateAllottedTime()
    {
        $totalAllottedTime = 0;
        foreach ($this->entity->tasks as $task) {
            if (! empty($task->revised_estimation)) {
                $totalAllottedTime += $task->revised_estimation;
            } else {
                $totalAllottedTime += $task->estimation;
            }
        }
        return $totalAllottedTime;
    }

    /**
     * @return int
     */
    public function calculateLoggedTimeTotal()
    {
        return $this->entity->tasks()->sum('logged_time');
    }
}
