<?php

namespace NeoClocking\Policies;

use NeoClocking\Models\Task;
use NeoClocking\Models\User;

class TaskPolicy
{
    /**
     * Validate if the user can create a task.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function create(User $user, Task $task)
    {
        return $user->can('manage', $task->project);
    }

    /**
     * Validate if the user can update the task.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function update(User $user, Task $task)
    {
        return $user->can('manage', $task->project);
    }

    /**
     * Validate if the user can view the task.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function view(User $user, Task $task)
    {
        return true;
    }

    /**
     * Validate if the user work on the task.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function workOn(User $user, Task $task)
    {
        return true;
    }

    /**
     * Validate if the user can add the task to his/her favourites.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function favourite(User $user, Task $task)
    {
        return true;
    }

    /**
     * Validate if the user can delete the task.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function delete(User $user, Task $task)
    {
        return $user->can('manage', $task->project);
    }
}
