<?php
namespace NeoClocking\Presenters;

use NeoClocking\Models\Task;

class TaskPresenter extends BasePresenter
{
    /** @var Task */
    protected $entity;

    /**
     * @return string
     */
    public function numberAndName()
    {
        return "#{$this->entity->number} - {$this->entity->name}";
    }

    /**
     * Format Estimated Time
     *
     * @return string
     */
    public function estimation()
    {
        return $this->minutesToHours($this->entity->estimation);
    }

    /**
     * Format Revised Estimated Time
     *
     * @return string
     */
    public function revisedEstimation()
    {
        return $this->minutesToHours($this->entity->revised_estimation);
    }

    /**
     * Format Logged Time
     *
     * @return string
     */
    public function loggedTime()
    {
        return $this->minutesToHours($this->entity->logged_time);
    }
}
