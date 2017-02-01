<?php
namespace NeoClocking\Presenters;

use NeoClocking\Models\User;

class UserPresenter extends BasePresenter
{
    /** @var  User */
    protected $entity;

    /**
     * First Name and Last name
     *
     * @return string
     */
    public function fullName()
    {
        return $this->entity->first_name . " " . $this->entity->last_name;
    }

    /**
     * Minutes the user would work per day
     * if said user worked equal hours each day
     *
     * @return int
     */
    public function dayDuration()
    {
        $duration = floor($this->entity->week_duration / 5);
        return $this->minutesToHours($duration);
    }
}
